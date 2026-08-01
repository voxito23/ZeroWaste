"""Protecciones estáticas para el despliegue y la migración de medios."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MigracionMediosProduccionTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.migracion = (ROOT / "scripts/migrar_medios_produccion.sh").read_text(
            encoding="utf-8"
        )
        cls.despliegue = (ROOT / "scripts/desplegar_produccion.sh").read_text(
            encoding="utf-8"
        )
        cls.docker = (ROOT / "scripts/funciones_docker.sh").read_text(
            encoding="utf-8"
        )

    def test_detecta_compose_moderno_y_clasico(self):
        self.assertIn("docker compose version", self.docker)
        self.assertIn("docker-compose version", self.docker)
        self.assertIn("COMANDO_COMPOSE", self.docker)

    def test_copia_todas_las_categorias_sin_sobrescribir(self):
        for categoria in (
            "foro",
            "perfiles",
            "recompensas",
            "campanas",
            "eventos",
            "puntos",
        ):
            self.assertIn(categoria, self.migracion)
        self.assertIn("sha256sum", self.migracion)
        self.assertNotIn("cp -f", self.migracion)
        self.assertNotIn("rm -rf", self.migracion)
        self.assertIn("conflictos-medios", self.migracion)

    def test_volumenes_heredados_son_solo_lectura_y_se_respaldan(self):
        self.assertIn("target=/origen,readonly", self.migracion)
        self.assertIn("volumen-${volumen}", self.migracion)
        self.assertNotIn("docker volume rm", self.migracion)

    def test_despliegue_usa_scripts_con_nombres_en_espanol(self):
        self.assertIn("migrar_medios_produccion.sh", self.despliegue)
        self.assertIn("desplegar_esquema_impacto.sh", self.despliegue)
        self.assertIn("verificar_medios_produccion.py", self.despliegue)


if __name__ == "__main__":
    unittest.main()
