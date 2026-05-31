import React, { PropsWithChildren, useEffect, useRef } from "react";
import {
  Animated,
  Easing,
  Image,
  LayoutAnimation,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TextInputProps,
  UIManager,
  View
} from "react-native";
import { LinearGradient } from "expo-linear-gradient";
import {
  Bell,
  ChartNoAxesColumn,
  FileText,
  Home,
  LogOut,
  MessageSquare,
  Search,
  Target,
  UserRound,
  Users
} from "./icons";
import { colors, radius, shadow, text } from "./theme";
import { assetUrl } from "./api";

const logoAsset = require("../assets/icon.png");
const premiumEase = Easing.bezier(0.34, 1.56, 0.64, 1);

if (Platform.OS === "android" && UIManager.setLayoutAnimationEnabledExperimental) {
  UIManager.setLayoutAnimationEnabledExperimental(true);
}

export function FadeIn({
  children,
  delay = 0,
  style
}: PropsWithChildren<{ delay?: number; style?: any }>) {
  const y = useRef(new Animated.Value(18)).current;
  const opacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(y, {
        toValue: 0,
        duration: 400,
        delay,
        easing: premiumEase,
        useNativeDriver: true
      }),
      Animated.timing(opacity, {
        toValue: 1,
        duration: 400,
        delay,
        easing: premiumEase,
        useNativeDriver: true
      })
    ]).start();
  }, [delay, opacity, y]);

  return (
    <Animated.View style={[style, { opacity, transform: [{ translateY: y }] }]}>
      {children}
    </Animated.View>
  );
}

export function Tap({
  children,
  onPress,
  style,
  accessibilityLabel
}: PropsWithChildren<{ onPress?: () => void; style?: any; accessibilityLabel?: string }>) {
  const scale = useRef(new Animated.Value(1)).current;
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      onPressIn={() =>
        Animated.spring(scale, { toValue: 0.95, useNativeDriver: true }).start()
      }
      onPressOut={() =>
        Animated.spring(scale, { toValue: 1, useNativeDriver: true }).start()
      }
    >
      <Animated.View style={[style, { transform: [{ scale }] }]}>
        {children}
      </Animated.View>
    </Pressable>
  );
}

export function Card({ children, style }: PropsWithChildren<{ style?: any }>) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function Eyebrow({ children }: PropsWithChildren) {
  return (
    <View style={styles.eyebrow}>
      <View style={styles.eyebrowDot} />
      <Text style={text.eyebrow}>{children}</Text>
    </View>
  );
}

export function Button({
  children,
  onPress,
  variant = "primary",
  icon
}: PropsWithChildren<{ onPress?: () => void; variant?: "primary" | "outline"; icon?: React.ReactNode }>) {
  const label = typeof children === "string" ? children : undefined;
  const content = (
    <View style={styles.buttonInner}>
      <Text style={[styles.buttonText, variant === "outline" && styles.buttonTextOutline]}>
        {children}
      </Text>
      {icon}
    </View>
  );

  if (variant === "outline") {
    return (
      <Tap onPress={onPress} style={styles.buttonOutline} accessibilityLabel={label}>
        {content}
      </Tap>
    );
  }

  return (
    <Tap onPress={onPress} style={styles.buttonShadow} accessibilityLabel={label}>
      <LinearGradient
        colors={[colors.primary, colors.primaryDark]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.button}
      >
        {content}
      </LinearGradient>
    </Tap>
  );
}

export function Input(props: TextInputProps & { label?: string }) {
  return (
    <View style={{ gap: 7 }}>
      {props.label ? <Text style={styles.label}>{props.label}</Text> : null}
      <TextInput
        {...props}
        placeholderTextColor="#95a0b4"
        style={[styles.input, props.style]}
      />
    </View>
  );
}

export function Header({
  title,
  subtitle,
  user,
  onProfile,
  onNotifications
}: {
  title: string;
  subtitle?: string;
  user?: { name?: string; email?: string; profile_photo_url?: string | null } | null;
  onProfile?: () => void;
  onNotifications?: () => void;
}) {
  const source = user?.profile_photo_url
    ? { uri: assetUrl(user.profile_photo_url) || user.profile_photo_url }
    : logoAsset;

  return (
    <View style={styles.header}>
      <Tap onPress={onProfile} style={styles.menuLogo} accessibilityLabel="Ouvrir le profil">
        <Image source={source} style={styles.menuLogoImage} resizeMode="contain" />
      </Tap>
      <View style={styles.headerCenter}>
        <Text style={styles.headerTitle}>{title}</Text>
        {subtitle ? <Text style={styles.headerSubtitle}>{subtitle}</Text> : null}
      </View>
      <Tap onPress={onNotifications} style={styles.iconButton}>
        <Bell color={colors.ink} size={20} strokeWidth={1.8} />
        <View style={styles.alertDot} />
      </Tap>
    </View>
  );
}

export const tabIcons: Record<string, React.ElementType> = {
  dashboard: Home,
  messages: MessageSquare,
  matching: Target,
  cvs: FileText,
  users: Users,
  requests: FileText,
  profile: UserRound,
  resources: ChartNoAxesColumn
};

export function BottomTabs({
  active,
  setActive,
  role,
  tabs: providedTabs
}: {
  active: string;
  setActive: (tab: string) => void;
  role: string;
  tabs?: [string, string][];
}) {
  const tabs = providedTabs || (
    role === "client"
      ? [
          ["dashboard", "Tableau"],
          ["requests", "Demandes"],
          ["messages", "Messages"],
          ["resources", "RH"],
          ["profile", "Profil"]
        ]
      : [
          ["dashboard", "Tableau"],
          ["messages", "Messages"],
          ["matching", "Analyses"],
          ["cvs", "CVs"],
          ["resources", "Planning"]
        ]
  );

  return (
    <View style={styles.tabsShell}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.tabs}
      >
      {tabs.map(([id, label]) => {
        const Icon = tabIcons[id] || Home;
        const isActive = active === id;
        return (
          <Tap
            key={id}
            onPress={() => {
              LayoutAnimation.configureNext(LayoutAnimation.Presets.easeInEaseOut);
              setActive(id);
            }}
            style={styles.tab}
          >
            <Icon color={isActive ? colors.primary : colors.muted} size={21} strokeWidth={1.8} />
            <Text style={[styles.tabLabel, isActive && styles.tabActive]}>
              {label}
            </Text>
            {isActive ? <View style={styles.tabDot} /> : null}
          </Tap>
        );
      })}
      </ScrollView>
    </View>
  );
}

export function Screen({
  children,
  title,
  subtitle,
  onProfile,
  onNotifications,
  user
}: PropsWithChildren<{
  title: string;
  subtitle?: string;
  onProfile?: () => void;
  onNotifications?: () => void;
  user?: { name?: string; email?: string; profile_photo_url?: string | null } | null;
}>) {
  return (
    <View style={styles.screen}>
      <Header
        title={title}
        subtitle={subtitle}
        user={user}
        onProfile={onProfile}
        onNotifications={onNotifications}
      />
      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
      >
        {children}
      </ScrollView>
    </View>
  );
}

export function SearchBar({
  placeholder = "Rechercher...",
  value,
  onChangeText
}: {
  placeholder?: string;
  value?: string;
  onChangeText?: (value: string) => void;
}) {
  return (
    <View style={styles.search}>
      <Search color={colors.muted} size={18} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#8b95a7"
        style={styles.searchInput}
      />
    </View>
  );
}

export function Sheet({
  visible,
  onClose,
  title,
  children
}: PropsWithChildren<{ visible: boolean; onClose: () => void; title: string }>) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Tap onPress={onClose} style={StyleSheet.absoluteFillObject} />
        <FadeIn style={styles.sheet}>
          <View style={styles.sheetHandle} />
          <View style={styles.sheetHeader}>
            <Text style={text.h2}>{title}</Text>
            <Tap onPress={onClose} style={styles.close}>
              <Text style={{ color: colors.primary, fontWeight: "900" }}>x</Text>
            </Tap>
          </View>
          <ScrollView showsVerticalScrollIndicator={false}>{children}</ScrollView>
        </FadeIn>
      </View>
    </Modal>
  );
}

export function LogoutRow({ onPress }: { onPress: () => void }) {
  return (
    <Tap onPress={onPress} style={styles.logout}>
      <LogOut color={colors.primary} size={18} />
      <Text style={{ color: colors.primary, fontWeight: "800" }}>Se deconnecter</Text>
    </Tap>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.bg },
  scroll: { padding: 18, paddingBottom: 112, gap: 16 },
  header: {
    paddingHorizontal: 18,
    paddingTop: 14,
    paddingBottom: 12,
    backgroundColor: colors.glass,
    borderBottomColor: colors.line,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 12,
    ...shadow
  },
  menuLogo: {
    width: 46,
    height: 46,
    alignItems: "center",
    justifyContent: "center",
    overflow: "visible"
  },
  menuLogoImage: { width: 42, height: 42 },
  headerCenter: {
    position: "absolute",
    left: 74,
    right: 74,
    top: 14,
    bottom: 12,
    alignItems: "center",
    justifyContent: "center"
  },
  headerTitle: { color: colors.ink, fontWeight: "900", fontSize: 16, textAlign: "center" },
  headerSubtitle: { color: colors.muted, fontSize: 11, marginTop: 2, textAlign: "center" },
  iconButton: {
    width: 39,
    height: 39,
    borderRadius: 20,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: "rgba(227,30,68,0.12)"
  },
  alertDot: {
    position: "absolute",
    top: 9,
    right: 9,
    width: 7,
    height: 7,
    borderRadius: 99,
    backgroundColor: colors.primary
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    padding: 18,
    ...shadow
  },
  eyebrow: {
    alignSelf: "flex-start",
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    backgroundColor: colors.primarySoft,
    borderColor: "#ffc7c0",
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 7
  },
  eyebrowDot: {
    width: 7,
    height: 7,
    borderRadius: 99,
    backgroundColor: colors.primary
  },
  buttonShadow: { borderRadius: 999, ...shadow },
  button: { borderRadius: 999, paddingVertical: 13, paddingHorizontal: 18 },
  buttonOutline: {
    borderColor: "#ff9d96",
    borderWidth: 1,
    borderRadius: 999,
    paddingVertical: 12,
    paddingHorizontal: 18,
    backgroundColor: "white"
  },
  buttonInner: { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 8 },
  buttonText: { color: "white", fontWeight: "900", fontSize: 15 },
  buttonTextOutline: { color: colors.primary },
  label: { color: colors.ink, fontSize: 11, fontWeight: "900", letterSpacing: 1.3, textTransform: "uppercase" },
  input: {
    minHeight: 50,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    paddingHorizontal: 14,
    color: colors.ink,
    backgroundColor: "white",
    fontWeight: "700"
  },
  tabsShell: {
    position: "absolute",
    left: 14,
    right: 14,
    bottom: 14,
    height: 68,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: colors.glass,
    overflow: "hidden",
    ...shadow
  },
  tabs: {
    minWidth: "100%",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-around",
    paddingHorizontal: 10,
    gap: 8
  },
  tab: { alignItems: "center", justifyContent: "center", minWidth: 58, height: 66, gap: 3 },
  tabLabel: { color: colors.muted, fontSize: 10, fontWeight: "800" },
  tabActive: { color: colors.primary },
  tabDot: { width: 6, height: 6, borderRadius: 99, backgroundColor: colors.primary, marginTop: 1 },
  search: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    minHeight: 48,
    paddingHorizontal: 14,
    backgroundColor: "#f5f6fa",
    borderRadius: radius.lg
  },
  searchInput: { flex: 1, color: colors.ink, fontWeight: "700" },
  overlay: {
    flex: 1,
    backgroundColor: "rgba(8,11,31,0.58)",
    justifyContent: "flex-end"
  },
  sheet: {
    maxHeight: "88%",
    backgroundColor: colors.bg,
    borderTopLeftRadius: radius.xl,
    borderTopRightRadius: radius.xl,
    padding: 18
  },
  sheetHandle: {
    alignSelf: "center",
    width: 48,
    height: 5,
    borderRadius: 99,
    backgroundColor: "#d6dbe5",
    marginBottom: 14
  },
  sheetHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 14
  },
  close: {
    width: 38,
    height: 38,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center"
  },
  logout: {
    flexDirection: "row",
    gap: 10,
    alignItems: "center",
    padding: 14,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft
  }
});
