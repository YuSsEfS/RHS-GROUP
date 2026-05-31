import AsyncStorage from "@react-native-async-storage/async-storage";
import { Platform } from "react-native";
import * as FileSystem from "expo-file-system";
import * as Sharing from "expo-sharing";

export type Role = "admin" | "employee" | "client";

export type User = {
  id: number | string;
  name: string;
  email: string;
  role: Role | string;
  phone?: string;
  profile_photo_url?: string | null;
  permissions?: string[];
};

export type ApiResult<T> = {
  data: T | null;
  error?: string;
};

export function collectionFrom<T = any>(payload: any): T[] {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload?.items)) return payload.items;
  if (Array.isArray(payload?.data?.data)) return payload.data.data;
  if (Array.isArray(payload?.data?.items)) return payload.data.items;
  return [];
}

const API_BASE_URL =
  Platform.OS === "android"
    ? "http://10.0.2.2:8000/api"
    : "http://127.0.0.1:8000/api";

const WEB_BASE_URL = API_BASE_URL.replace(/\/api$/, "");

const TOKEN_KEY = "rhs_mobile_token";
const DEFAULT_TIMEZONE = "Africa/Casablanca";

export function currentTimeZone() {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || DEFAULT_TIMEZONE;
  } catch {
    return DEFAULT_TIMEZONE;
  }
}

export async function setToken(token: string | null) {
  if (token) {
    await AsyncStorage.setItem(TOKEN_KEY, token);
  } else {
    await AsyncStorage.removeItem(TOKEN_KEY);
  }
}

export async function getToken() {
  return AsyncStorage.getItem(TOKEN_KEY);
}

export function assetUrl(url?: string | null) {
  if (!url) return null;
  if (Platform.OS !== "android") return url;
  return url.replace("http://127.0.0.1:8000", "http://10.0.2.2:8000").replace("http://localhost:8000", "http://10.0.2.2:8000");
}

export function publicAsset(path: string) {
  const normalized = path.startsWith("/") ? path : `/${path}`;
  return assetUrl(`${WEB_BASE_URL}${normalized}`);
}

function safeFileName(name?: string | null) {
  return (name || `rhs-file-${Date.now()}`)
    .replace(/[^\w.\-]+/g, "_")
    .replace(/^_+/, "")
    .slice(0, 140);
}

async function downloadAndOpen(path: string, filename?: string | null): Promise<ApiResult<{ uri: string }>> {
  try {
    const token = await getToken();
    const target = `${FileSystem.cacheDirectory || ""}${safeFileName(filename)}`;
    const result = await FileSystem.downloadAsync(`${API_BASE_URL}${path}`, target, {
      headers: token ? { Authorization: `Bearer ${token}` } : undefined
    });

    if (result.status < 200 || result.status >= 300) {
      return { data: null, error: `Telechargement impossible (${result.status})` };
    }

    if (await Sharing.isAvailableAsync()) {
      await Sharing.shareAsync(result.uri, {
        dialogTitle: filename || "Ouvrir le CV",
      });
    }

    return { data: { uri: result.uri } };
  } catch (error) {
    return {
      data: null,
      error: error instanceof Error ? error.message : "Impossible d ouvrir le fichier"
    };
  }
}

async function request<T>(
  path: string,
  options: RequestInit = {}
): Promise<ApiResult<T>> {
  try {
    const token = await getToken();
    const isFormData = typeof FormData !== "undefined" && options.body instanceof FormData;
    const response = await fetch(`${API_BASE_URL}${path}`, {
      ...options,
      headers: {
        Accept: "application/json",
        "X-User-Timezone": currentTimeZone(),
        ...(!isFormData ? { "Content-Type": "application/json" } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(options.headers || {})
      }
    });

    const json = await response.json().catch(() => null);

    if (!response.ok) {
      return {
        data: null,
        error: json?.message || `Erreur API ${response.status}`
      };
    }

    return { data: json as T };
  } catch (error) {
    return {
      data: null,
      error: error instanceof Error ? error.message : "Erreur reseau"
    };
  }
}

export const api = {
  baseUrl: API_BASE_URL,

  async login(email: string, password: string) {
    const result = await request<{ token?: string; access_token?: string; user?: User }>(
      "/login",
      {
        method: "POST",
        body: JSON.stringify({ email, password, device_name: "rhs-mobile" })
      }
    );

    const token = result.data?.token || result.data?.access_token;
    if (token) await setToken(token);
    return result;
  },

  me: () => request<{ user?: User } | User>("/me"),
  updateProfile: (payload: {
    name: string;
    email: string;
    profile_photo?: { uri: string; name?: string | null; mimeType?: string | null };
    remove_profile_photo?: boolean;
  }) => {
    const form = new FormData();
    form.append("name", payload.name);
    form.append("email", payload.email);
    if (payload.remove_profile_photo) form.append("remove_profile_photo", "1");
    if (payload.profile_photo) {
      form.append("profile_photo", {
        uri: payload.profile_photo.uri,
        name: payload.profile_photo.name || safeFileName(payload.profile_photo.uri),
        type: payload.profile_photo.mimeType || "image/jpeg"
      } as any);
    }

    return request<{ user?: User }>("/profile", {
      method: "POST",
      body: form as any
    });
  },
  logout: () => request("/logout", { method: "POST" }).finally(() => setToken(null)),
  dashboard: () => request<any>("/dashboard"),
  notifications: () => request<any>("/notifications"),
  registerNotificationDevice: (payload: {
    expo_push_token: string;
    platform?: string;
    device_name?: string;
  }) =>
    request<any>("/notifications/device", {
      method: "POST",
      body: JSON.stringify(payload)
    }),
  debugNotificationDevice: (payload: {
    stage: string;
    status?: string;
    message?: string;
    details?: Record<string, unknown>;
    platform?: string;
    project_id?: string;
  }) =>
    request<any>("/notifications/device/debug", {
      method: "POST",
      body: JSON.stringify(payload)
    }),
  messages: () => request<any[]>("/messages"),
  messageTargets: () => request<any[]>("/messages/targets"),
  createConversation: (payload: {
    participant_user_id: number | string;
    subject: string;
    body: string;
    priority?: string;
    attachments?: Array<{ uri: string; name?: string | null; mimeType?: string | null }>;
  }) => {
    const form = new FormData();
    form.append("participant_user_id", String(payload.participant_user_id));
    form.append("subject", payload.subject);
    form.append("priority", payload.priority || "normal");
    if (payload.body.trim()) form.append("body", payload.body.trim());
    (payload.attachments || []).forEach((file) => {
      form.append("attachments[]", {
        uri: file.uri,
        name: file.name || safeFileName(file.uri),
        type: file.mimeType || "application/octet-stream"
      } as any);
    });

    return request<any>("/messages", {
      method: "POST",
      body: form as any
    });
  },
  conversation: (id: number | string) => request<any>(`/messages/${id}`),
  sendMessage: (
    id: number | string,
    body: string,
    attachments: Array<{ uri: string; name?: string | null; mimeType?: string | null }> = []
  ) => {
    if (attachments.length) {
      const form = new FormData();
      if (body.trim()) form.append("body", body.trim());
      attachments.forEach((file) => {
        form.append("attachments[]", {
          uri: file.uri,
          name: file.name || safeFileName(file.uri),
          type: file.mimeType || "application/octet-stream"
        } as any);
      });

      return request<any>(`/messages/${id}`, {
        method: "POST",
        body: form as any
      });
    }

    return request<any>(`/messages/${id}`, {
      method: "POST",
      body: JSON.stringify({ body, message: body })
    });
  },
  openMessageAttachment: (id: number | string, filename?: string | null) => downloadAndOpen(`/messages/attachments/${id}/download`, filename),
  messageAttachmentUrl: (id: number | string) => `${API_BASE_URL}/messages/attachments/${id}/download`,
  resources: () => request<any[]>("/rh-resources"),
  resourceDetails: (id: number | string) => request<any>(`/rh-resources/${id}`),
  resourceDownloadUrl: (id: number | string) => `${WEB_BASE_URL}/api/rh-resources/${id}/download`,
  meetings: () => request<any[]>("/meetings"),
  meetingDetails: (id: number | string) => request<any>(`/meetings/${id}`),
  createMeeting: (payload: {
    title: string;
    description?: string;
    meeting_date: string;
    start_time: string;
    end_time?: string;
    location?: string;
    online_link?: string;
    participants: Array<number | string>;
  }) =>
    request<any>("/meetings", {
      method: "POST",
      body: JSON.stringify(payload)
    }),
  requests: () => request<any[]>("/recruitment-requests"),
  createRequest: (payload: any) =>
    request<any>("/recruitment-requests", {
      method: "POST",
      body: JSON.stringify(payload)
    }),
  requestDetails: (id: number | string) => request<any>(`/recruitment-requests/${id}`),
  cvs: (q = "") => request<any[]>(`/cvs${q ? `?q=${encodeURIComponent(q)}` : ""}`),
  cvDetails: (id: number | string) => request<any>(`/cvs/${id}`),
  openCv: (id: number | string, filename?: string | null) => downloadAndOpen(`/cvs/${id}/download`, filename),
  externalCvBatches: () => request<any[]>("/external-cvs"),
  externalCvBatch: (id: number | string) => request<any>(`/external-cvs/${id}`),
  openExternalCv: (id: number | string, filename?: string | null) => downloadAndOpen(`/external-cv-files/${id}/download`, filename),

  users: () => request<any[]>("/users"),
  matchingJobs: () => request<any[]>("/matching"),
  matchingDetails: (id: number | string) => request<any>(`/matching/${id}`),
  cancelMatching: (id: number | string) =>
    request<any>(`/matching/${id}/cancel`, { method: "POST" })
};
