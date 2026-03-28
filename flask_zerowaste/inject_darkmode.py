import os
import re

template_dir = 'templates'

dark_mode_script = """
<script>
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        }
    }
</script>
"""

for filename in os.listdir(template_dir):
    if filename.endswith('.html'):
        filepath = os.path.join(template_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Verificar si el script ya fue inyectado previamente
        if "function toggleTheme()" not in content:
            # Insertar antes de la etiqueta </head>
            content = content.replace('</head>', dark_mode_script + '</head>')
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)

print("Script de modo oscuro inyectado correctamente.")
