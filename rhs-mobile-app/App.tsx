import React, { useEffect, useState } from "react";
import { ActivityIndicator, View } from "react-native";
import { SafeAreaProvider, SafeAreaView } from "react-native-safe-area-context";
import { StatusBar } from "expo-status-bar";
import { api, getToken, setToken, User } from "./src/api";
import { AppShell, LoginScreen } from "./src/screens";
import { colors } from "./src/theme";

export default function App() {
  const [booting, setBooting] = useState(true);
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    async function boot() {
      const token = await getToken();
      if (token) {
        const result = await api.me();
        const me = (result.data as any)?.user || result.data;
        if (me) setUser(me as User);
      }
      setBooting(false);
    }
    boot();
  }, []);

  async function logout() {
    await api.logout();
    await setToken(null);
    setUser(null);
  }

  return (
    <SafeAreaProvider>
      <SafeAreaView style={{ flex: 1, backgroundColor: colors.bg }}>
        <StatusBar style="dark" />
        {booting ? (
          <View style={{ flex: 1, alignItems: "center", justifyContent: "center" }}>
            <ActivityIndicator color={colors.primary} />
          </View>
        ) : user ? (
          <AppShell user={user} onLogout={logout} onUserUpdate={setUser} />
        ) : (
          <LoginScreen onLogin={setUser} />
        )}
      </SafeAreaView>
    </SafeAreaProvider>
  );
}
