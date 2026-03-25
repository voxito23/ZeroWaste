import os
import re

template_dir = 'templates'
css_link = '    <link href="{{ url_for(\'static\', filename=\'css/output.css\') }}" rel="stylesheet">\n'

for filename in os.listdir(template_dir):
    if filename.endswith('.html'):
        filepath = os.path.join(template_dir, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Remove the CDN tag
        content = re.sub(r'<script src="https://cdn\.tailwindcss\.com.*"></script>\n?', '', content)

        # Remove the tailwind.config block
        content = re.sub(r'<script>\s*tailwind\.config = \{.*?\};\s*</script>\n?', '', content, flags=re.DOTALL)

        # Insert the CSS link right after <title> or </title>
        # To be safe, insert it right before </head>
        content = re.sub(r'</head>', css_link.rstrip() + '\n</head>', content)

        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)

print("Tailwind replacement complete.")
