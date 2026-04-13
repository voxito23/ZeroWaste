import os
import re

directory = r"C:\ZeroWaste\flask_zerowaste\templates"
files = ['foro.html', 'recomendaciones.html', 'contacto.html', 'Acercade.html', 'noticia-queretaro.html', 'perfil.html', 'nuevo_post.html', 'post.html']

for filename in files:
    filepath = os.path.join(directory, filename)
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if "{% extends 'base.html' %}" in content:
        print(f"Skipping {filename}, already extends base.html")
        continue

    # Extraer estilos embebidos
    style_content = ""
    style_match = re.search(r'<style.*?>(.*?)</style>', content, re.DOTALL)
    if style_match:
        style_content = style_match.group(1).strip()
    
    # Extraer bloques de scripts del archivo
    scripts_content = ""
    # Se buscan scripts que no correspondan a Tailwind ni Google Fonts
    
    # Construir el bloque superior con herencia de plantilla
    top_replacement = "{% extends 'base.html' %}\n\n"
    if style_content:
        top_replacement += f"{{% block styles %}}\n<style>\n{style_content}\n</style>\n{{% endblock %}}\n\n"
    
    top_replacement += "{% block content %}\n"
    
    # Dividir el contenido por la inclusión del header
    parts = content.split("{% include 'includes/header.html' %}")
    if len(parts) > 1:
        content = top_replacement + parts[1]
    
    # Eliminar cualquier bloque de footer estático residual
    content = re.sub(r'<footer.*?</footer>', '', content, flags=re.DOTALL)
    
    # Procesar la parte inferior: footer y scripts adicionales
    bottom_parts = content.split("{% include 'includes/footer.html' %}")
    if len(bottom_parts) > 1:
        # Extraer scripts del bloque inferior
        body_content = bottom_parts[1].replace('</body></html>', '').strip()
        body_content = body_content.replace('</body>', '').replace('</html>', '').strip()
        
        bottom_replacement = "\n{% endblock %}\n"
        if body_content:
            bottom_replacement += f"\n{{% block scripts %}}\n{body_content}\n{{% endblock %}}\n"
            
        content = bottom_parts[0] + bottom_replacement
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Refactored {filename} successfully.")
