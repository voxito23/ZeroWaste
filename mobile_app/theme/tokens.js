const colors = Object.freeze({
  forest: '#064E3B',
  forestDeep: '#022C22',
  green: '#059669',
  greenBright: '#10B981',
  mint: '#ECFDF5',
  mintSoft: '#D1FAE5',
  white: '#FFFFFF',
  surface: '#F8FAFC',
  text: '#0F172A',
  textSecondary: '#64748B',
  border: '#E2E8F0',
  error: '#DC2626',
});

const motion = Object.freeze({
  press: 140,
  micro: 180,
  navigation: 220,
  skeleton: 200,
});

const radius = Object.freeze({ card: 24, pill: 999, control: 16 });

module.exports = { colors, motion, radius };
