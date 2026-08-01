-- PROPUESTA LOCAL: no ejecutar directamente en producción.
-- PostgreSQL/Supabase. La fuente ejecutable y reversible son las migraciones
-- Laravel 2026_08_01_000001 a 000004.
BEGIN;

ALTER TABLE locations ADD COLUMN activo boolean NOT NULL DEFAULT true;
ALTER TABLE locations ADD COLUMN horario varchar(255);
ALTER TABLE locations ADD COLUMN responsable varchar(150);
ALTER TABLE locations ADD COLUMN deleted_at timestamptz;

CREATE TABLE point_qr_codes (
  id bigserial PRIMARY KEY,
  location_id bigint NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
  token_hash char(64) NOT NULL UNIQUE,
  token_ciphertext text NOT NULL,
  version integer NOT NULL DEFAULT 1,
  active boolean NOT NULL DEFAULT true,
  generated_at timestamptz NOT NULL,
  regenerated_at timestamptz,
  revoked_at timestamptz,
  created_by bigint REFERENCES usuarios(id) ON DELETE SET NULL,
  created_at timestamptz, updated_at timestamptz
);
CREATE INDEX point_qr_location_active_idx ON point_qr_codes(location_id, active);
CREATE UNIQUE INDEX uq_point_qr_one_active ON point_qr_codes(location_id) WHERE active = true;

CREATE TABLE collection_schedules (
  id bigserial PRIMARY KEY,
  weekday smallint NOT NULL UNIQUE CHECK (weekday BETWEEN 1 AND 7),
  active boolean NOT NULL DEFAULT false,
  starts_at time NOT NULL DEFAULT '10:00', ends_at time NOT NULL DEFAULT '14:00',
  interval_minutes smallint NOT NULL DEFAULT 60 CHECK (interval_minutes > 0),
  capacity_per_interval smallint NOT NULL DEFAULT 10 CHECK (capacity_per_interval > 0),
  updated_by bigint REFERENCES usuarios(id) ON DELETE SET NULL,
  created_at timestamptz, updated_at timestamptz
);
INSERT INTO collection_schedules(weekday,active,starts_at,ends_at,interval_minutes,capacity_per_interval)
SELECT d, d IN (1,3,5), '10:00', '14:00', 60, 10 FROM generate_series(1,7) AS d;

CREATE TABLE schedule_exceptions (
  id bigserial PRIMARY KEY, exception_date date NOT NULL, kind varchar(30) NOT NULL DEFAULT 'closed',
  starts_at time, ends_at time, capacity_per_interval smallint, reason varchar(255) NOT NULL,
  active boolean NOT NULL DEFAULT true, created_by bigint REFERENCES usuarios(id) ON DELETE SET NULL,
  created_at timestamptz, updated_at timestamptz, UNIQUE(exception_date, kind)
);
ALTER TABLE solicitudes_recoleccion ADD COLUMN cantidad_estimada varchar(100);
ALTER TABLE solicitudes_recoleccion ADD COLUMN notas text;
ALTER TABLE solicitudes_recoleccion ADD COLUMN scheduled_at timestamptz;
ALTER TABLE solicitudes_recoleccion ADD COLUMN folio varchar(30) UNIQUE;
ALTER TABLE tokens_qr_recoleccion ADD COLUMN token_ciphertext text;
ALTER TABLE tokens_qr_recoleccion ADD COLUMN version integer NOT NULL DEFAULT 1;
ALTER TABLE tokens_qr_recoleccion ADD COLUMN status varchar(20) NOT NULL DEFAULT 'active';
ALTER TABLE tokens_qr_recoleccion ADD COLUMN invalidated_at timestamptz;

ALTER TABLE usuarios ADD COLUMN email_verified_at timestamptz;
CREATE TABLE oauth_accounts (
  id bigserial PRIMARY KEY, usuario_id bigint NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  provider varchar(30) NOT NULL, provider_subject varchar(255) NOT NULL, provider_email varchar(255),
  linked_at timestamptz NOT NULL, last_login_at timestamptz,
  UNIQUE(provider,provider_subject), UNIQUE(usuario_id,provider)
);
CREATE TABLE oauth_login_states (
  id bigserial PRIMARY KEY, state_hash char(64) NOT NULL UNIQUE, verifier_ciphertext text NOT NULL,
  nonce_hash char(64) NOT NULL, handoff_hash char(64) UNIQUE, claims_ciphertext text,
  usuario_id bigint REFERENCES usuarios(id) ON DELETE CASCADE, status varchar(30) NOT NULL DEFAULT 'pending',
  expires_at timestamptz NOT NULL, used_at timestamptz, created_at timestamptz, updated_at timestamptz
);
CREATE TABLE email_verification_tokens (
  id bigserial PRIMARY KEY, usuario_id bigint NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
  token_hash char(64) NOT NULL UNIQUE, expires_at timestamptz NOT NULL, used_at timestamptz,
  revoked_at timestamptz, provider_message_id varchar(255), sent_at timestamptz,
  created_at timestamptz, updated_at timestamptz
);

ALTER TABLE recompensas ADD COLUMN available_at timestamptz;
ALTER TABLE recompensas ADD COLUMN deleted_at timestamptz;
CREATE TABLE point_rule_history (
  id bigserial PRIMARY KEY, rule_id bigint NOT NULL REFERENCES reglas_puntos(id) ON DELETE CASCADE,
  before_values jsonb NOT NULL, after_values jsonb NOT NULL,
  administrator_id bigint REFERENCES usuarios(id) ON DELETE SET NULL, created_at timestamptz NOT NULL
);
CREATE TABLE audit_logs (
  id bigserial PRIMARY KEY, administrator_id bigint REFERENCES usuarios(id) ON DELETE SET NULL,
  action varchar(80) NOT NULL, subject_type varchar(80) NOT NULL, subject_id varchar(100),
  metadata jsonb, ip_hash varchar(64), created_at timestamptz NOT NULL
);
INSERT INTO reglas_puntos(codigo,descripcion,puntos,limite_diario,activa,created_at,updated_at)
VALUES ('VISITA_PUNTO_QR','Visita verificada en punto mediante QR',0,1,false,now(),now())
ON CONFLICT (codigo) DO NOTHING;

-- Esta transacción queda deliberadamente sin COMMIT para revisión humana.
ROLLBACK;
