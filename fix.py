import re

path = r'c:\ZeroWaste\fast_api\app\models\domain_models.py'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

# Buscamos la primera ocurrencia de "class Evento(Base):" 
# Sabemos que está alrededor de la línea 76.
pattern = r"# Modelo de evento\s+class Evento\(Base\):\s+__tablename__ = \"eventos\"\s+id = Column\(Integer, primary_key=True, index=True\)\s+titulo = Column\(String\(150\), nullable=False\)\s+fecha_inicio = Column\(DateTime, nullable=False\)\s+ubicacion = Column\(String\(255\), nullable=False\)\s+descripcion = Column\(Text, nullable=False\)\s+categoria = Column\(String\(100\), nullable=True\)\s+imagen = Column\(String\(255\), nullable=True\)\s+link_unirse = Column\(String\(255\), nullable=True\)"

new_text = re.sub(pattern, '# Modelo antiguo removido', text)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_text)

print("Duplicates removed")
