import os
import re

directory = r'C:\ZeroWaste\flask_zerowaste\templates'
files_to_process = [
    'recomendaciones.html',
    'post.html',
    'perfil.html',
    'nuevo_post.html',
    'noticia-queretaro.html',
    'contacto.html'
]

header_pattern = re.compile(r'<header id="smart-header".*?</header>', re.DOTALL)
footer_pattern = re.compile(r'<footer class="bg-secondary text-white relative overflow-hidden.*?(?:mt-auto)?.*?</footer>', re.DOTALL)
script_pattern_1 = re.compile(r'document\.addEventListener\("DOMContentLoaded", \(\) => \{\s*const header = document\.getElementById\("smart-header"\);\s*let lastScrollY = window\.scrollY;\s*window\.addEventListener\("scroll", \(\) => \{\s*if \(window\.scrollY > lastScrollY && window\.scrollY > 80\) \{?\s*header\.classList\.add\("-translate-y-full"\);\s*\}? else \{?\s*header\.classList\.remove\("-translate-y-full"\);\s*\}?\s*lastScrollY = Math\.max\(0, window\.scrollY\);\s*\}\);\s*\}\);', re.DOTALL)
script_pattern_1_singleline = re.compile(r'document\.addEventListener\("DOMContentLoaded", \(\) => \{\s*const header = document\.getElementById\("smart-header"\);\s*let lastScrollY = window\.scrollY;\s*window\.addEventListener\("scroll", \(\) => \{\s*if \(window\.scrollY > lastScrollY && window\.scrollY > 80\) header\.classList\.add\("-translate-y-full"\);\s*else header\.classList\.remove\("-translate-y-full"\);\s*lastScrollY = Math\.max\(0, window\.scrollY\);\s*\}\);\s*.*?\s*\}\);', re.DOTALL)
script_pattern_2 = re.compile(r'// Animación Header\s*document\.addEventListener\("DOMContentLoaded".*?\}\);', re.DOTALL)

for file_name in files_to_process:
    filepath = os.path.join(directory, file_name)
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Replace Header
        content = header_pattern.sub("{% include 'includes/header.html' %}", content)
        
        # Replace Footer
        content = footer_pattern.sub("{% include 'includes/footer.html' %}", content)
        
        # Remove Header scroll JS
        content = script_pattern_1.sub("", content)
        content = script_pattern_1_singleline.sub("", content)
        content = script_pattern_2.sub("", content)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Processed: {file_name}")
    else:
        print(f"Not found: {file_name}")
