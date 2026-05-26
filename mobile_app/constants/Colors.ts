/**
 * ZeroWaste — Paleta completa de colores para Light y Dark mode.
 * Basada en la estética del sitio Flask original:
 *   Primary  = #00E096 (verde brillante)
 *   Secondary = #064E3B (verde bosque)
 *   Forest-dark = #022C22 (superficie oscura)
 */

export const Colors = {
  // ─── Brand ─────────────────────────────
  primary: '#00E096',
  primaryHover: '#00C281',
  primaryMuted: 'rgba(0, 224, 150, 0.15)',

  // ─── Category colors (Foro) ────────────
  category: {
    reciclaje: '#F59E0B',
    compostaje: '#10B981',
    reduccion: '#06B6D4',
    eventos: '#8B5CF6',
    dudas: '#F43F5E',
  },

  // ─── Light Theme ───────────────────────
  light: {
    // Surfaces
    background: '#F9FAFB',       // gray-50
    surface: '#FFFFFF',
    surfaceSecondary: '#F0FDF4', // emerald-50
    card: '#FFFFFF',
    cardBorder: '#E5E7EB',       // gray-200

    // Text
    text: '#111827',             // gray-900
    textSecondary: '#6B7280',    // gray-500
    textMuted: '#9CA3AF',        // gray-400
    textInverse: '#FFFFFF',

    // Accent
    tint: '#064E3B',             // secondary (dark green)
    accent: '#00E096',

    // Tab bar
    tabBar: '#FFFFFF',
    tabBarBorder: '#E5E7EB',
    tabIconDefault: '#9CA3AF',
    tabIconSelected: '#10B981',

    // Input
    inputBg: '#F9FAFB',
    inputBorder: '#D1D5DB',      // gray-300
    inputText: '#111827',
    placeholder: '#9CA3AF',

    // Status
    success: '#10B981',
    error: '#EF4444',
    warning: '#F59E0B',
    info: '#3B82F6',

    // Specific
    headerBg: '#FFFFFF',
    divider: '#E5E7EB',
    skeleton: '#E5E7EB',
    overlay: 'rgba(0, 0, 0, 0.5)',
    shadow: 'rgba(0, 0, 0, 0.08)',
  },

  // ─── Dark Theme ────────────────────────
  dark: {
    // Surfaces
    background: '#022C22',       // forest-dark
    surface: '#064E3B',          // secondary
    surfaceSecondary: '#0A3D2F',
    card: '#053929',
    cardBorder: '#0D5C44',

    // Text
    text: '#ECFDF5',             // emerald-50
    textSecondary: '#A7F3D0',    // emerald-200
    textMuted: '#6EE7B7',        // emerald-300 (softened)
    textInverse: '#022C22',

    // Accent
    tint: '#00E096',
    accent: '#00E096',

    // Tab bar
    tabBar: '#022C22',
    tabBarBorder: '#064E3B',
    tabIconDefault: '#6EE7B7',
    tabIconSelected: '#00E096',

    // Input
    inputBg: '#053929',
    inputBorder: '#0D5C44',
    inputText: '#ECFDF5',
    placeholder: '#6EE7B7',

    // Status
    success: '#34D399',
    error: '#F87171',
    warning: '#FBBF24',
    info: '#60A5FA',

    // Specific
    headerBg: '#022C22',
    divider: '#064E3B',
    skeleton: '#064E3B',
    overlay: 'rgba(0, 0, 0, 0.7)',
    shadow: 'rgba(0, 0, 0, 0.3)',
  },
};

// Helper type for accessing themed colors
export type ThemeColors = typeof Colors.light;
