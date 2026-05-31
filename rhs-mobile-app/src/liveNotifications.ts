import { useEffect, useRef } from "react";
import { Alert, AppState, AppStateStatus, Platform } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";
import Constants from "expo-constants";
import { api } from "./api";
import { User } from "./api";

type NotificationCounts = Record<string, number>;

type LiveNotificationOptions = {
  enabled: boolean;
  user: User | null;
  onNavigate?: (screen: string) => void;
};

const POLL_INTERVAL_MS = 12000;
const SNAPSHOT_PREFIX = "rhs_notification_snapshot";
let notificationsModule: any | null | undefined;
const ENABLE_NATIVE_NOTIFICATIONS = true;

const watchedItems: Array<{
  key: string;
  title: string;
  body: string;
  target: string;
}> = [
  {
    key: "conversations",
    title: "Nouveau message",
    body: "Une conversation RHS a recu une nouvelle reponse.",
    target: "messages"
  },
  {
    key: "meetings",
    title: "Nouvelle reunion",
    body: "Une reunion est disponible dans votre planning.",
    target: "resources"
  },
  {
    key: "matching_history",
    title: "Matching termine",
    body: "Des resultats de matching sont prets a consulter.",
    target: "matching"
  },
  {
    key: "client_alerts",
    title: "Relance client",
    body: "Une relance client attend votre suivi.",
    target: "requests"
  },
  {
    key: "assigned_requests",
    title: "Nouvelle demande assignee",
    body: "Une demande client a ete assignee a votre espace.",
    target: "requests"
  },
  {
    key: "client_requests",
    title: "Nouvelle demande client",
    body: "Une demande client attend traitement.",
    target: "requests"
  },
  {
    key: "external_batches",
    title: "Traitement CV externe",
    body: "Un lot de CV externe demande votre attention.",
    target: "cvs"
  },
  {
    key: "cv_imports",
    title: "Import CV",
    body: "Un import CV est en attente ou en cours.",
    target: "cvs"
  }
];

export function useLiveNotifications({
  enabled,
  user,
  onNavigate
}: LiveNotificationOptions) {
  const lastCounts = useRef<NotificationCounts | null>(null);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const pollingRef = useRef(false);
  const appStateRef = useRef<AppStateStatus>(AppState.currentState);

  useEffect(() => {
    if (!enabled || !user) return;

    let mounted = true;
    const currentUser = user;
    const snapshotKey = `${SNAPSHOT_PREFIX}.${user.id}`;

    async function start() {
      const notifications = await configureNotifications(currentUser);
      lastCounts.current = await loadSnapshot(snapshotKey);
      await poll(true, notifications);

      intervalRef.current = setInterval(() => {
        poll(false, notifications);
      }, POLL_INTERVAL_MS);
    }

    async function poll(isBoot = false, notifications: any | null = null) {
      if (!mounted || pollingRef.current) return;
      pollingRef.current = true;

      try {
        const result = await api.notifications();
        const data = result.data as any;
        const counts = normalizeCounts(data?.items || data || {});
        const previous = lastCounts.current;

        if (previous && appStateRef.current !== "inactive") {
          await notifyIncreases(previous, counts, notifications);
        }

        lastCounts.current = counts;
        await AsyncStorage.setItem(snapshotKey, JSON.stringify(counts));
      } finally {
        pollingRef.current = false;
      }
    }

    const appStateSubscription = AppState.addEventListener("change", (state) => {
      appStateRef.current = state;
      if (state === "active") poll(false, getNotificationsModule());
    });

    const notifications = getNotificationsModule();
    const responseSubscription = notifications?.addNotificationResponseReceivedListener?.((response: any) => {
      const target = response.notification.request.content.data?.target;
      if (typeof target === "string") onNavigate?.(target);
    });

    start();

    return () => {
      mounted = false;
      if (intervalRef.current) clearInterval(intervalRef.current);
      appStateSubscription.remove();
      responseSubscription?.remove?.();
    };
  }, [enabled, onNavigate, user]);
}

function getNotificationsModule() {
  if (!ENABLE_NATIVE_NOTIFICATIONS) return null;
  if (notificationsModule !== undefined) return notificationsModule;

  try {
    notificationsModule = require("expo-notifications");
  } catch {
    notificationsModule = null;
  }

  return notificationsModule;
}

async function configureNotifications(user: User) {
  const Notifications = getNotificationsModule();
  if (!Notifications) {
    await reportPushDiagnostic("module", "missing", "expo-notifications native module is not available");
    return null;
  }

  Notifications.setNotificationHandler?.({
    handleNotification: async () => ({
      shouldShowAlert: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
      shouldShowBanner: true,
      shouldShowList: true
    })
  });

  if (Platform.OS === "android") {
    await Notifications.setNotificationChannelAsync("rhs-live", {
      name: "RHS live alerts",
      importance: Notifications.AndroidImportance.HIGH,
      vibrationPattern: [0, 250, 150, 250],
      lightColor: "#e31e44"
    });
  }

  const current = await Notifications.getPermissionsAsync() as any;
  if (current.status !== "granted" && current.granted !== true) {
    const requested = await Notifications.requestPermissionsAsync({
      ios: {
        allowAlert: true,
        allowBadge: true,
        allowSound: true
      }
    });

    if (requested.status !== "granted" && requested.granted !== true) {
      await reportPushDiagnostic("permission", "denied", "Notification permission was not granted", { requested });
      return Notifications;
    }
  }

  await registerExpoPushToken(Notifications, user);

  return Notifications;
}

async function registerExpoPushToken(Notifications: any, user: User) {
  const projectId =
    Constants.easConfig?.projectId
    || Constants.expoConfig?.extra?.eas?.projectId
    || Constants.manifest2?.extra?.eas?.projectId;

  try {
    if (!projectId) {
      await reportPushDiagnostic("project_id", "missing", "No EAS project id found in app config");
      return;
    }

    const result = await Notifications.getExpoPushTokenAsync(projectId ? { projectId } : undefined);
    const token = result?.data;

    if (!token) {
      await reportPushDiagnostic("expo_token", "empty", "Expo returned an empty push token", { result }, projectId);
      return;
    }

    const registration = await api.registerNotificationDevice({
      expo_push_token: token,
      platform: Platform.OS,
      device_name: `${Platform.OS}-${user.id}`
    });

    if (!registration.data) {
      await reportPushDiagnostic("backend_register", "failed", registration.error || "Backend rejected push token", { tokenPrefix: token.slice(0, 22) }, projectId);
    }
  } catch (error) {
    // A development build without Expo push configuration can still use live polling.
    console.warn("RHS push token registration failed", error);
    await reportPushDiagnostic(
      "expo_token",
      "failed",
      error instanceof Error ? error.message : String(error),
      {
        name: error instanceof Error ? error.name : undefined,
        stack: error instanceof Error ? error.stack?.slice(0, 1200) : undefined
      },
      projectId
    );
  }
}

async function reportPushDiagnostic(
  stage: string,
  status: string,
  message: string,
  details: Record<string, unknown> = {},
  projectId?: string | null
) {
  try {
    await api.debugNotificationDevice({
      stage,
      status,
      message,
      details,
      platform: Platform.OS,
      project_id: projectId || undefined
    });
  } catch {
    // Diagnostics should never block the app.
  }
}

async function loadSnapshot(key: string): Promise<NotificationCounts | null> {
  const raw = await AsyncStorage.getItem(key);
  if (!raw) return null;

  try {
    return JSON.parse(raw) as NotificationCounts;
  } catch {
    return null;
  }
}

function normalizeCounts(items: Record<string, unknown>): NotificationCounts {
  return Object.entries(items).reduce<NotificationCounts>((counts, [key, value]) => {
    counts[key] = Number(value || 0);
    return counts;
  }, {});
}

async function notifyIncreases(previous: NotificationCounts, current: NotificationCounts, notifications: any | null) {
  for (const item of watchedItems) {
    const before = previous[item.key] || 0;
    const now = current[item.key] || 0;

    if (now > before) {
      if (!notifications?.scheduleNotificationAsync) {
        Alert.alert(item.title, now - before > 1 ? `${item.body} (+${now - before})` : item.body);
        continue;
      }

      const identifier = `rhs-${item.key}`;
      await notifications.dismissNotificationAsync?.(identifier).catch?.(() => null);
      await notifications.scheduleNotificationAsync({
        identifier,
        content: {
          title: item.title,
          body: now - before > 1 ? `${item.body} (+${now - before})` : item.body,
          data: { target: item.target, key: item.key },
          sound: "default",
          priority: "high",
          color: "#e31e44"
        },
        trigger: null
      });
    }
  }
}
