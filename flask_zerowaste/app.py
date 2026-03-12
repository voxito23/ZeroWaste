from flask import Flask, render_template, request, redirect, url_for, jsonify, session
import mysql.connector
import os
import uuid

app = Flask(__name__)
# Llave secreta necesaria para mantener las sesiones activas
app.secret_key = 'super_secreta_zerowaste_2026'

def get_db_connection():
    # En Docker, el host es el nombre del servicio en el docker-compose (en este caso "db")
    conexion = mysql.connector.connect(
        host="db",                
        user="root",              
        password="rootpassword",  
        database="zerowaste_db",  
        port=3306
    )
    return conexion

# --- RUTAS DE NAVEGACIÓN BÁSICA ---
@app.route('/')
def root(): 
    return render_template('login.html')

@app.route('/login')
def login(): 
    return render_template('login.html')

@app.route('/registro')
def registro(): 
    return render_template('registro.html')

@app.route('/inicio')
def index(): 
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    return render_template('index.html')

@app.route('/noticia-queretaro')
def noticia_queretaro(): 
    return render_template('noticia-queretaro.html')

@app.route('/Acercade')
def Acercade(): 
    return render_template('acercade.html')

@app.route('/mapa')
def mapa(): 
    return render_template('mapa.html')

@app.route('/contacto')
def contacto(): 
    return render_template('contacto.html')


# --- RUTAS DEL PERFIL ---
@app.route('/perfil')
def perfil(): 
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    return render_template('perfil.html')

@app.route('/editar_perfil', methods=['POST'])
def editar_perfil():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
        
    nombre = request.form.get('nombre')
    ubicacion = request.form.get('ubicacion')
    titulo_perfil = request.form.get('titulo_perfil')
    biografia = request.form.get('biografia')
    intereses = request.form.get('intereses')
    
    conexion = get_db_connection()
    cursor = conexion.cursor()
    try:
        sql = """UPDATE usuarios SET nombre=%s, ubicacion=%s, titulo_perfil=%s, 
                 biografia=%s, intereses=%s WHERE id=%s"""
        cursor.execute(sql, (nombre, ubicacion, titulo_perfil, biografia, intereses, session['usuario_id']))
        conexion.commit()
        
        # Actualizamos la sesión para ver los cambios instantáneos
        session['nombre'] = nombre
        session['ubicacion'] = ubicacion
        session['titulo_perfil'] = titulo_perfil
        session['biografia'] = biografia
        session['intereses'] = intereses
    except Exception as e:
        print("Error al actualizar:", e)
    finally:
        cursor.close()
        conexion.close()
        
    return redirect(url_for('perfil'))


# --- RUTAS DEL FORO ---
@app.route('/foro')
def foro(): 
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    return render_template('foro.html')

# Corregido: El HTML busca 'ver_post', no 'post'
@app.route('/foro/post')
def ver_post(): 
    return render_template('post.html')

# Corregido: El HTML busca 'nuevo_post', no 'nuevopost'
@app.route('/foro/nuevo')
def nuevo_post(): 
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    return render_template('nuevo_post.html')

@app.route('/crear_post', methods=['POST'])
def crear_post():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    
    titulo = request.form.get('titulo')
    categoria_id = request.form.get('categoria_id')
    contenido = request.form.get('contenido')
    
    conexion = get_db_connection()
    cursor = conexion.cursor()
    try:
        sql = "INSERT INTO posts (usuario_id, categoria_id, titulo, contenido) VALUES (%s, %s, %s, %s)"
        cursor.execute(sql, (session['usuario_id'], categoria_id, titulo, contenido))
        conexion.commit()
    except Exception as e:
        print("Error al crear post:", e)
    finally:
        cursor.close()
        conexion.close()
        
    return redirect(url_for('foro'))


# --- AUTENTICACIÓN ---
@app.route('/logout')
def logout():
    session.clear() 
    return redirect(url_for('login'))

@app.route('/handle_login', methods=['POST'])
def handle_login():
    email_usuario = request.form.get('email')
    password_usuario = request.form.get('password')

    conexion = get_db_connection()
    cursor = conexion.cursor(dictionary=True) 

    try:
        sql = "SELECT * FROM usuarios WHERE email = %s AND password = %s"
        cursor.execute(sql, (email_usuario, password_usuario))
        usuario_encontrado = cursor.fetchone()

        if usuario_encontrado:
            # Guardamos todos los datos necesarios en sesión
            session['usuario_id'] = usuario_encontrado['id']
            session['nombre'] = usuario_encontrado['nombre']
            session['email'] = usuario_encontrado['email']
            session['foto_perfil'] = usuario_encontrado['foto_perfil']
            session['titulo_perfil'] = usuario_encontrado['titulo_perfil']
            session['biografia'] = usuario_encontrado['biografia']
            session['ubicacion'] = usuario_encontrado['ubicacion']
            session['intereses'] = usuario_encontrado['intereses']
            
            return jsonify({ 'success': True, 'redirect': url_for('index') })
        else:
            return jsonify({ 'success': False, 'error': 'Correo o contraseña incorrectos.' })
    except mysql.connector.Error as err:
        return jsonify({ 'success': False, 'error': 'Error de conexión a la base de datos.' })
    finally:
        if cursor: cursor.close()
        if conexion: conexion.close()

@app.route('/handle_registro', methods=['POST'])
def handle_registro():
    nombre_usuario = request.form.get('nombre')
    email_usuario = request.form.get('email')
    password_usuario = request.form.get('password')
    foto_perfil_file = request.files.get('foto_perfil')

    if not foto_perfil_file:
        return jsonify({ 'success': False, 'error': 'La foto de perfil es obligatoria.' })

    extension = foto_perfil_file.filename.split('.')[-1].lower()
    nombre_foto = f"{uuid.uuid4().hex}.{extension}"
    
    carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
    os.makedirs(carpeta_destino, exist_ok=True) 
    ruta_completa = os.path.join(carpeta_destino, nombre_foto)
    foto_perfil_file.save(ruta_completa)

    conexion = get_db_connection()
    cursor = conexion.cursor()

    try:
        sql = "INSERT INTO usuarios (nombre, email, password, foto_perfil) VALUES (%s, %s, %s, %s)"
        valores = (nombre_usuario, email_usuario, password_usuario, nombre_foto)
        cursor.execute(sql, valores)
        conexion.commit() 
        
        session['usuario_id'] = cursor.lastrowid
        session['nombre'] = nombre_usuario
        session['email'] = email_usuario
        session['foto_perfil'] = nombre_foto
        session['titulo_perfil'] = 'Entusiasta de la Sostenibilidad'
        session['biografia'] = ''
        session['ubicacion'] = 'Querétaro, México'
        session['intereses'] = 'Reciclaje'
        
        return jsonify({ 'success': True, 'redirect': url_for('index') })
    except mysql.connector.Error as err:
        return jsonify({ 'success': False, 'error': 'El correo electrónico ya está registrado.' })
    finally:
        if cursor: cursor.close()
        if conexion: conexion.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)