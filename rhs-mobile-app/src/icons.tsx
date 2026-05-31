import React from "react";
import { StyleSheet, View } from "react-native";

type IconProps = {
  color?: string;
  size?: number;
  strokeWidth?: number;
};

function Frame({ size, children }: React.PropsWithChildren<{ size: number }>) {
  return <View style={{ width: size, height: size }}>{children}</View>;
}

function line(color: string, strokeWidth: number) {
  return {
    position: "absolute" as const,
    height: strokeWidth,
    borderRadius: strokeWidth,
    backgroundColor: color
  };
}

function ring(color: string, strokeWidth: number, size: number) {
  return {
    width: size,
    height: size,
    borderRadius: size / 2,
    borderWidth: strokeWidth,
    borderColor: color
  };
}

export function Home({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[line(color, strokeWidth), styles.homeRoofLeft, { width: size * 0.45, top: size * 0.28, left: size * 0.18, transform: [{ rotate: "-38deg" }] }]} />
      <View style={[line(color, strokeWidth), styles.homeRoofRight, { width: size * 0.45, top: size * 0.28, right: size * 0.18, transform: [{ rotate: "38deg" }] }]} />
      <View style={[styles.homeBase, { borderColor: color, borderWidth: strokeWidth, width: size * 0.56, height: size * 0.46, left: size * 0.22, top: size * 0.42 }]} />
    </Frame>
  );
}

export function Bell({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[styles.bellBody, { borderColor: color, borderWidth: strokeWidth, width: size * 0.54, height: size * 0.58, left: size * 0.23, top: size * 0.18, borderRadius: size * 0.28 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.64, left: size * 0.18, top: size * 0.72 }]} />
      <View style={[ring(color, strokeWidth, size * 0.14), { position: "absolute", left: size * 0.43, top: size * 0.79 }]} />
    </Frame>
  );
}

export function ChartNoAxesColumn({ color = "#111827", size = 20 }: IconProps) {
  return (
    <Frame size={size}>
      {[0.46, 0.64, 0.3].map((height, index) => (
        <View
          key={index}
          style={{
            position: "absolute",
            bottom: size * 0.16,
            left: size * (0.18 + index * 0.24),
            width: size * 0.13,
            height: size * height,
            borderRadius: 3,
            backgroundColor: color
          }}
        />
      ))}
    </Frame>
  );
}

export function Check({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[line(color, strokeWidth), { width: size * 0.28, left: size * 0.18, top: size * 0.55, transform: [{ rotate: "42deg" }] }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.58, left: size * 0.36, top: size * 0.46, transform: [{ rotate: "-45deg" }] }]} />
    </Frame>
  );
}

export function Clock({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[ring(color, strokeWidth, size * 0.76), { position: "absolute", left: size * 0.12, top: size * 0.12 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.24, left: size * 0.5, top: size * 0.5 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.22, left: size * 0.4, top: size * 0.42, transform: [{ rotate: "90deg" }] }]} />
    </Frame>
  );
}

export function FileText({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[styles.file, { borderColor: color, borderWidth: strokeWidth, width: size * 0.62, height: size * 0.76, left: size * 0.19, top: size * 0.1, borderRadius: 4 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.34, left: size * 0.33, top: size * 0.43 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.28, left: size * 0.33, top: size * 0.58 }]} />
    </Frame>
  );
}

export function MessageSquare({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[styles.message, { borderColor: color, borderWidth: strokeWidth, width: size * 0.72, height: size * 0.58, left: size * 0.14, top: size * 0.18, borderRadius: 5 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.18, left: size * 0.56, top: size * 0.73, transform: [{ rotate: "42deg" }] }]} />
    </Frame>
  );
}

export function MessageCircle(props: IconProps) {
  return <MessageSquare {...props} />;
}

export function Plus({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[line(color, strokeWidth), { width: size * 0.64, left: size * 0.18, top: size * 0.49 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.64, left: size * 0.18, top: size * 0.49, transform: [{ rotate: "90deg" }] }]} />
    </Frame>
  );
}

export function Search({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[ring(color, strokeWidth, size * 0.52), { position: "absolute", left: size * 0.16, top: size * 0.14 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.32, left: size * 0.58, top: size * 0.67, transform: [{ rotate: "45deg" }] }]} />
    </Frame>
  );
}

export function Send({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[line(color, strokeWidth), { width: size * 0.72, left: size * 0.12, top: size * 0.5 }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.28, right: size * 0.12, top: size * 0.39, transform: [{ rotate: "36deg" }] }]} />
      <View style={[line(color, strokeWidth), { width: size * 0.28, right: size * 0.12, top: size * 0.6, transform: [{ rotate: "-36deg" }] }]} />
    </Frame>
  );
}

export function Target({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[ring(color, strokeWidth, size * 0.78), { position: "absolute", left: size * 0.11, top: size * 0.11 }]} />
      <View style={[ring(color, strokeWidth, size * 0.38), { position: "absolute", left: size * 0.31, top: size * 0.31 }]} />
    </Frame>
  );
}

export function UserRound({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[ring(color, strokeWidth, size * 0.28), { position: "absolute", left: size * 0.36, top: size * 0.16 }]} />
      <View style={[styles.userBase, { borderColor: color, borderWidth: strokeWidth, width: size * 0.62, height: size * 0.34, left: size * 0.19, top: size * 0.54, borderRadius: size * 0.22 }]} />
    </Frame>
  );
}

export function Users({ color = "#111827", size = 20, strokeWidth = 1.8 }: IconProps) {
  return (
    <Frame size={size}>
      <View style={[ring(color, strokeWidth, size * 0.24), { position: "absolute", left: size * 0.22, top: size * 0.2 }]} />
      <View style={[ring(color, strokeWidth, size * 0.24), { position: "absolute", right: size * 0.22, top: size * 0.2 }]} />
      <View style={[styles.userBase, { borderColor: color, borderWidth: strokeWidth, width: size * 0.36, height: size * 0.28, left: size * 0.12, top: size * 0.56, borderRadius: size * 0.18 }]} />
      <View style={[styles.userBase, { borderColor: color, borderWidth: strokeWidth, width: size * 0.36, height: size * 0.28, right: size * 0.12, top: size * 0.56, borderRadius: size * 0.18 }]} />
    </Frame>
  );
}

export function UserPlus(props: IconProps) {
  return <Users {...props} />;
}

export function ArrowRight(props: IconProps) {
  return <Send {...props} />;
}

export function LogOut(props: IconProps) {
  return <ArrowRight {...props} />;
}

const styles = StyleSheet.create({
  homeRoofLeft: { position: "absolute" },
  homeRoofRight: { position: "absolute" },
  homeBase: { position: "absolute", borderTopWidth: 0 },
  bellBody: { position: "absolute", borderBottomWidth: 0 },
  file: { position: "absolute" },
  message: { position: "absolute" },
  userBase: { position: "absolute", borderTopWidth: 0 }
});
