from flask import Flask, render_template, request, redirect, url_for

app = Flask(__name__)

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
    return render_template('index.html')

@app.route('/noticia-queretaro')
def noticia_queretaro():
    return render_template('noticia-queretaro.html')

@app.route('/foro')
def foro():
    return render_template('foro.html')

@app.route('/nuevopost')
def nuevopost():
    return render_template('nuevopost.html')

@app.route('/post')
def post():
    return render_template('post.html')

@app.route('/Acercade')
def Acercade():
    return render_template('Acercade.html')

@app.route('/mapa')
def mapa():
    return render_template('mapa.html')

@app.route('/recomendaciones')
def recomendaciones():
    return render_template('recomendaciones.html')

@app.route('/contacto')
def contacto():
    return render_template('contacto.html')

@app.route('/perfil')
def perfil():
    return render_template('perfil.html')

@app.route('/handle_login', methods=['POST'])
def handle_login():
    return redirect(url_for('index'))

@app.route('/handle_registro', methods=['POST'])
def handle_registro():
    return redirect(url_for('login'))

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)