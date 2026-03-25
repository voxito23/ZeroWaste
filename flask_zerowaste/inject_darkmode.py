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

        # Check if already injected
        if "function toggleTheme()" not in content:
            # Insert just before </head>
            content = content.replace('</head>', dark_mode_script + '</head>')
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)

print("Dark mode script injected.")
