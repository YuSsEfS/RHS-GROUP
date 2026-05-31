import { Platform, StyleSheet } from "react-native";

export const colors = {
  bg: "#f7f9ff",
  surface: "#ffffff",
  ink: "#080b1f",
  muted: "#667085",
  line: "#e6eaf4",
  primary: "#e31e44",
  primaryDark: "#b90f31",
  primarySoft: "#ffe8ee",
  blush: "#fff2f5",
  success: "#16a34a",
  warning: "#f59e0b",
  danger: "#e31e44",
  navy: "#111827",
  glass: "rgba(255,255,255,0.82)"
};

export const radius = {
  sm: 6,
  md: 8,
  lg: 8,
  xl: 12
};

export const shadow = Platform.select({
  ios: {
    shadowColor: "#0f172a",
    shadowOpacity: 0.08,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 }
  },
  android: { elevation: 4 },
  default: {}
});

export const text = StyleSheet.create({
  eyebrow: {
    color: colors.primary,
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 2.6,
    textTransform: "uppercase"
  },
  h1: {
    color: colors.ink,
    fontSize: 34,
    lineHeight: 40,
    fontWeight: "900",
    letterSpacing: -0.2
  },
  h2: {
    color: colors.ink,
    fontSize: 25,
    lineHeight: 31,
    fontWeight: "900"
  },
  h3: {
    color: colors.ink,
    fontSize: 18,
    lineHeight: 24,
    fontWeight: "800"
  },
  body: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 23
  },
  small: {
    color: colors.muted,
    fontSize: 12,
    lineHeight: 17
  }
});
