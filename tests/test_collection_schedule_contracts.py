import sys
import os
import types
import importlib.util
import unittest
from datetime import date, datetime, time
from pathlib import Path
from types import SimpleNamespace

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "fast_api"))
os.environ.setdefault("DATABASE_URL", "postgresql://127.0.0.1:5432/zerowaste_contract_test")

from sqlalchemy.orm import declarative_base

data_package = types.ModuleType("app.data")
data_package.__path__ = []
database_module = types.ModuleType("app.data.database")
database_module.Base = declarative_base()
sys.modules.setdefault("app.data", data_package)
sys.modules.setdefault("app.data.database", database_module)

models_package = types.ModuleType("app.models")
models_package.__path__ = []
sys.modules.setdefault("app.models", models_package)
domain_spec = importlib.util.spec_from_file_location("app.models.domain_models", ROOT / "fast_api" / "app" / "models" / "domain_models.py")
domain_module = importlib.util.module_from_spec(domain_spec)
sys.modules["app.models.domain_models"] = domain_module
domain_spec.loader.exec_module(domain_module)

from app.models.domain_models import CollectionSchedule, ScheduleException  # noqa: E402
from app.services.collection_schedule import (  # noqa: E402
    LOCAL_TZ,
    SCHEDULE_MESSAGE,
    ScheduleValidationError,
    available_slots,
    validate_slot,
)


class Query:
    def __init__(self, db, entity):
        self.db = db
        self.entity = entity

    def filter_by(self, **kwargs):
        self.kwargs = kwargs
        return self

    def order_by(self, *_args):
        return self

    def filter(self, *_args):
        return self

    def first(self):
        if self.entity is ScheduleException:
            return self.db.exception
        if self.entity is CollectionSchedule:
            weekday = self.kwargs.get("weekday")
            if weekday in {1, 3, 5}:
                return SimpleNamespace(starts_at=time(10), ends_at=time(14), interval_minutes=60, capacity_per_interval=self.db.capacity)
        return None

    def scalar(self):
        return self.db.occupied


class FakeDb:
    def __init__(self, occupied=0, capacity=2, exception=None):
        self.occupied = occupied
        self.capacity = capacity
        self.exception = exception

    def query(self, entity):
        return Query(self, entity)


class CollectionScheduleContractsTest(unittest.TestCase):
    def test_monday_open_and_boundary_slots(self):
        db = FakeDb()
        self.assertIsNotNone(validate_slot(db, datetime(2026, 8, 3, 10, tzinfo=LOCAL_TZ)))
        self.assertIsNotNone(validate_slot(db, datetime(2026, 8, 3, 14, tzinfo=LOCAL_TZ)))
        self.assertEqual(len(available_slots(db, date(2026, 8, 3))), 5)

    def test_tuesday_and_outside_hours_are_rejected(self):
        db = FakeDb()
        for value in [datetime(2026, 8, 4, 10, tzinfo=LOCAL_TZ), datetime(2026, 8, 3, 9, tzinfo=LOCAL_TZ), datetime(2026, 8, 3, 14, 30, tzinfo=LOCAL_TZ)]:
            with self.assertRaisesRegex(ScheduleValidationError, SCHEDULE_MESSAGE):
                validate_slot(db, value)

    def test_capacity_and_closed_exception_are_enforced(self):
        with self.assertRaisesRegex(ScheduleValidationError, "No hay horarios"):
            validate_slot(FakeDb(occupied=2, capacity=2), datetime(2026, 8, 5, 10, tzinfo=LOCAL_TZ))
        closed = SimpleNamespace(kind="closed", starts_at=None, ends_at=None, capacity_per_interval=None)
        with self.assertRaisesRegex(ScheduleValidationError, SCHEDULE_MESSAGE):
            validate_slot(FakeDb(exception=closed), datetime(2026, 8, 5, 10, tzinfo=LOCAL_TZ))

    def test_collection_completion_contract_locks_and_awards_once(self):
        source = (ROOT / "fast_api" / "app" / "services" / "collection_qr.py").read_text(encoding="utf-8")
        self.assertGreaterEqual(source.count("with_for_update()"), 2)
        self.assertIn('qr.status = "used"', source)
        self.assertIn('rule_code="RECOLECCION_QR"', source)
        self.assertIn('"WRONG_COLLECTION"', source)
        migration = (ROOT / "laravel_zerowaste" / "database" / "migrations" / "2026_07_31_000000_create_impact_and_rewards_tables.php").read_text(encoding="utf-8")
        self.assertIn("unique(['usuario_id', 'referencia_tipo', 'referencia_id', 'regla_id']", migration)

    def test_collection_qr_generation_locks_the_request_row(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "recoleccion.py").read_text(encoding="utf-8")
        start = source.index("def generar_qr_recoleccion")
        end = source.index("def completar_recoleccion_qr")
        self.assertIn("with_for_update().first()", source[start:end])

    def test_collectors_cannot_create_collection_requests(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "recoleccion.py").read_text(encoding="utf-8")
        start = source.index("def solicitar_recoleccion")
        end = source.index('@router.get("", response_model=List[SolicitudRecoleccionResponse]')
        contract = source[start:end]
        self.assertIn('current_user.rol == "recolector"', contract)
        self.assertIn("status_code=403", contract)

    def test_collectors_are_notified_and_cannot_rate_collectors(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "recoleccion.py").read_text(encoding="utf-8")
        create_block = source[source.index("def solicitar_recoleccion"):source.index('@router.get("", response_model=List[SolicitudRecoleccionResponse]')]
        rating_block = source[source.index("def calificar_recolector"):source.index('@router.post("/{solicitud_id}/qr"')]
        self.assertIn('notification_type = "collection_created"', create_block)
        self.assertIn('Usuario.rol == "recolector"', create_block)
        self.assertIn('"requesterName": requester_name', create_block)
        self.assertIn('"requesterAvatarUrl": build_public_avatar_url', create_block)
        self.assertIn('"latitude": float(nueva_solicitud.latitud)', create_block)
        self.assertIn('"address": destination', create_block)
        self.assertIn('"scheduledAt": scheduled_at.isoformat()', create_block)
        self.assertIn('body = f"{requester_name} · Destino: {destination}"', create_block)
        self.assertIn("background_tasks.add_task(send_expo_push", create_block)
        self.assertIn('current_user.rol == "recolector"', rating_block)
        self.assertIn("HTTP_403_FORBIDDEN", rating_block)
        self.assertIn("calificacion_recolector is not None", rating_block)


if __name__ == "__main__":
    unittest.main()
