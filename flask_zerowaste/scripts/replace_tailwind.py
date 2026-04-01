import os
import re

template_dir = 'templates'
css_link = '    <link href="{{ url_for(\'static\', filename=\'css/output.css\') }}" rel="stylesheet">\n'

for filename in os.listdir(template_dir):
    if filename.endswith('.html'):
        filepath = os.path.join(template_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Eliminar la etiqueta del CDN de Tailwind
        content = re.sub(r'<script src="https://cdn\.tailwindcss\.com.*"></script>\n?', '', content)

        # Eliminar el bloque de configuración de Tailwind
        content = re.sub(r'<script>\s*tailwind\.config = \{.*?\};\s*</script>\n?', '', content, flags=re.DOTALL)

        # Insertar el enlace CSS local antes de </head>
        content = re.sub(r'</head>', css_link.rstrip() + '\n</head>', content)

        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)

print("Reemplazo de Tailwind CDN completado.")
