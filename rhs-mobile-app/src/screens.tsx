import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Animated,
  AppState,
  Easing,
  Image,
  InteractionManager,
  Linking,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import {
  ArrowRight,
  Check,
  Clock,
  FileText,
  MessageCircle,
  Plus,
  Send,
  Target,
  UserPlus
} from "./icons";
import { LinearGradient } from "expo-linear-gradient";
import * as DocumentPicker from "expo-document-picker";
import { api, assetUrl, collectionFrom, getToken, User } from "./api";
import {
  BottomTabs,
  Button,
  Card,
  Eyebrow,
  FadeIn,
  Input,
  LogoutRow,
  Screen,
  SearchBar,
  Sheet,
  Tap
} from "./components";
import { colors, radius, shadow, text } from "./theme";
import { useLiveNotifications } from "./liveNotifications";

const rhsLogo = require("../assets/icon.png");
const premiumEase = Easing.bezier(0.34, 1.56, 0.64, 1);

export function LoginScreen({
  onLogin
}: {
  onLogin: (user: User) => void;
}) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit() {
    setLoading(true);
    setError(null);
    const result = await api.login(email, password);
    if (result.data) {
      const me = await api.me();
      const user = (me.data as any)?.user || me.data || result.data.user;
      onLogin(
        user || {
          id: 1,
          name: "RHS Admin",
          email,
          role: "admin"
        }
      );
    } else {
      setError(result.error || "Connexion impossible");
    }
    setLoading(false);
  }

  return (
    <LinearGradient colors={[colors.primary, colors.primaryDark]} style={styles.loginRoot}>
      <View style={styles.loginHalo} />
      <FadeIn style={styles.loginCard}>
        <View style={styles.loginLogo}>
          <Image source={rhsLogo} style={styles.loginLogoImage} resizeMode="contain" />
        </View>
        <Eyebrow>espace prive</Eyebrow>
        <Text style={[text.h1, { marginTop: 16 }]}>Bienvenue sur RHS Group</Text>
        <Text style={[text.body, { marginTop: 8 }]}>
          Connectez-vous pour gerer vos demandes, matchings, messages et
          ressources RH.
        </Text>
        <View style={{ gap: 12, marginTop: 22 }}>
          <Input label="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" placeholder="contact@rhsgroup.ma" />
          <Input label="Mot de passe" value={password} onChangeText={setPassword} secureTextEntry placeholder="********" />
          {error ? <Text style={styles.error}>{error}</Text> : null}
          <Button onPress={submit}>
            {loading ? "Connexion..." : "Se connecter"}
          </Button>
          {loading ? <ActivityIndicator color={colors.primary} /> : null}
        </View>
      </FadeIn>
    </LinearGradient>
  );
}

export function DashboardScreen(props: ScreenProps) {
  const [dashboard, setDashboard] = useState<any | null>(null);

  useEffect(() => {
    api.dashboard().then((result) => {
      if (result.data) setDashboard(result.data);
    });
  }, []);

  const metricItems = collectionFrom<any>(dashboard?.metrics);
  const activityItems = collectionFrom<any>(dashboard?.activity);
  const stats = dashboard?.stats || {};
  const modules = featureCatalog(props.user || null, stats, metricItems);

  return (
    <Screen title="Tableau de bord" subtitle="Vue d'ensemble de votre activite" {...props}>
      <FadeIn>
        <LinearGradient
          colors={[colors.primary, colors.primaryDark]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.heroCard}
        >
          <Eyebrow>rhs group</Eyebrow>
          <Text style={[text.h1, { color: "white", marginTop: 16 }]}>
            Pilotez vos operations RH en temps reel
          </Text>
          <Text style={[text.body, { color: "rgba(255,255,255,0.85)", marginTop: 10 }]}>
            Suivez les demandes, les matchings, les messages et les actions prioritaires depuis un seul espace mobile.
          </Text>
          <View style={styles.heroActions}>
            <Button variant="outline" icon={<Plus color={colors.primary} size={16} />} onPress={() => props.onNavigate?.("requests")}>Nouvelle demande</Button>
          </View>
        </LinearGradient>
      </FadeIn>

      <View style={styles.metricsGrid}>
        {metricItems.map((item, index) => (
          <FadeIn key={item.label} delay={index * 55} style={styles.metricCell}>
            <Card style={{ minHeight: 128 }}>
              <View style={styles.metricIcon}>
                <FileText color={colors.primary} size={18} />
              </View>
              <Text style={styles.metricValue}>{item.value.toLocaleString("fr-FR")}</Text>
              <Text style={text.small}>{item.label}</Text>
            </Card>
          </FadeIn>
        ))}
      </View>

      <FadeIn delay={220}>
        <Card style={{ gap: 14 }}>
          <Text style={text.h3}>Modules operationnels</Text>
          <View style={styles.moduleList}>
            {modules.map((feature) => (
              <Tap key={feature.key} onPress={() => props.onNavigate?.(feature.target)} style={styles.moduleTile} accessibilityLabel={feature.label}>
                <View style={styles.moduleIcon}>
                  <Text style={styles.moduleIconText}>{feature.label.slice(0, 1)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.moduleLabel}>{feature.label}</Text>
                  <Text style={styles.moduleHint}>{feature.description}</Text>
                </View>
                <Text style={styles.moduleValue}>{feature.value}</Text>
              </Tap>
            ))}
          </View>
        </Card>
      </FadeIn>

      <FadeIn delay={250}>
        <Card style={{ gap: 12 }}>
          <Text style={text.h3}>Vue complete</Text>
          <View style={styles.statRows}>
            <StatLine label="Demandes totales" value={stats.requests?.total || 0} />
            <StatLine label="Matching en cours" value={stats.matching?.processing || 0} />
            <StatLine label="Candidats selectionnes" value={stats.matching?.selected || 0} />
            <StatLine label="CV archives" value={stats.library?.archived_cvs || 0} />
            <StatLine label="Lots externes en traitement" value={stats.library?.external_processing || 0} />
          </View>
        </Card>
      </FadeIn>

      <FadeIn delay={260}>
        <Card>
          <Text style={text.h3}>Activite recente</Text>
          {activityItems.length ? activityItems.map((item) => (
            <View key={item.id || item.title} style={styles.activityRow}>
              <View style={styles.checkCircle}><Check color={colors.primary} size={14} /></View>
              <Text style={styles.activityText}>{item.title || item.body}</Text>
            </View>
          )) : <Text style={[text.body, { marginTop: 12 }]}>Aucune activite recente.</Text>}
        </Card>
      </FadeIn>
    </Screen>
  );
}

export function MessagesScreen(props: ScreenProps) {
  const [items, setItems] = useState<any[]>([]);
  const [active, setActive] = useState<any | null>(null);
  const [messages, setMessages] = useState<any[]>([]);
  const [loadingThread, setLoadingThread] = useState(false);
  const [body, setBody] = useState("");
  const [conversationQuery, setConversationQuery] = useState("");
  const [conversationFilter, setConversationFilter] = useState("all");
  const [messageQuery, setMessageQuery] = useState("");
  const [messageSearchOpen, setMessageSearchOpen] = useState(false);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [previewAttachment, setPreviewAttachment] = useState<any | null>(null);
  const [attachmentToken, setAttachmentToken] = useState<string | null>(null);
  const [attachments, setAttachments] = useState<DocumentPicker.DocumentPickerAsset[]>([]);
  const [newOpen, setNewOpen] = useState(false);
  const [targets, setTargets] = useState<any[]>([]);
  const [targetQuery, setTargetQuery] = useState("");
  const [draft, setDraft] = useState({
    participant_user_id: "",
    subject: "",
    body: "",
    priority: "normal"
  });
  const [draftAttachments, setDraftAttachments] = useState<DocumentPicker.DocumentPickerAsset[]>([]);
  const setTabsHidden = props.onTabsHiddenChange;
  const messageScrollRef = useRef<ScrollView | null>(null);
  const activeConversationRef = useRef<any | null>(null);
  const refreshThreadRef = useRef(false);

  const scrollThreadToBottom = useCallback((animated = false) => {
    [0, 80, 220].forEach((delay) => {
      setTimeout(() => {
        InteractionManager.runAfterInteractions(() => {
          messageScrollRef.current?.scrollToEnd({ animated });
        });
      }, delay);
    });
  }, []);

  const loadConversations = useCallback(() => {
    return api.messages().then((result) => {
      setItems(collectionFrom(result.data));
    });
  }, []);

  useEffect(() => {
    loadConversations();
    const interval = setInterval(loadConversations, 10000);
    const subscription = AppState.addEventListener("change", (state) => {
      if (state === "active") loadConversations();
    });

    return () => {
      clearInterval(interval);
      subscription.remove();
    };
  }, [loadConversations]);

  useEffect(() => {
    getToken().then(setAttachmentToken);
  }, []);

  useEffect(() => {
    activeConversationRef.current = active;
  }, [active]);

  useEffect(() => {
    setTabsHidden?.(Boolean(active));
    return () => setTabsHidden?.(false);
  }, [active, setTabsHidden]);

  useEffect(() => {
    if (!active) return;
    scrollThreadToBottom(false);
  }, [active, messageQuery, messages.length, scrollThreadToBottom]);

  const refreshActiveConversation = useCallback(async (showSpinner = false) => {
    const conversation = activeConversationRef.current;
    if (!conversation?.id || refreshThreadRef.current) return;

    refreshThreadRef.current = true;
    if (showSpinner) setLoadingThread(true);

    const result = await api.conversation(conversation.id);
    if (result.data) {
      const nextMessages = sortMessages(collectionFrom((result.data as any).messages));
      setActive((current: any | null) => current?.id === conversation.id ? { ...current, ...result.data } : current);
      setMessages(nextMessages);
      loadConversations();
      scrollThreadToBottom(false);
    }

    refreshThreadRef.current = false;
    if (showSpinner) setLoadingThread(false);
  }, [loadConversations, scrollThreadToBottom]);

  useEffect(() => {
    if (!active?.id) return;

    const interval = setInterval(() => refreshActiveConversation(false), 3500);
    const subscription = AppState.addEventListener("change", (state) => {
      if (state === "active") refreshActiveConversation(false);
    });

    return () => {
      clearInterval(interval);
      subscription.remove();
    };
  }, [active?.id, refreshActiveConversation]);

  useEffect(() => {
    if (!newOpen) return;

    api.messageTargets().then((result) => {
      if (result.data) setTargets(collectionFrom(result.data));
    });
  }, [newOpen]);

  const filteredItems = useMemo(() => {
    return items.filter((conversation) => {
      const haystack = normalizeForSearch([
        conversation.name,
        conversation.title,
        conversation.subtitle,
        conversation.preview,
        conversation.priority_label,
        conversation.status
      ].filter(Boolean).join(" "));
      const matchesQuery = !conversationQuery.trim() || haystack.includes(normalizeForSearch(conversationQuery));
      const matchesFilter =
        conversationFilter === "all"
        || (conversationFilter === "unread" && Number(conversation.unread_count || 0) > 0)
        || (conversationFilter === "group" && (conversation.is_group || conversation.type === "group"))
        || (conversationFilter === "urgent" && conversation.priority === "urgent")
        || (conversationFilter === "attachments" && conversation.has_attachments);

      return matchesQuery && matchesFilter;
    });
  }, [conversationFilter, conversationQuery, items]);

  const visibleMessages = useMemo(() => {
    if (!messageQuery.trim()) return messages;

    const query = normalizeForSearch(messageQuery);
    return messages.filter((message) => normalizeForSearch([
      message.body,
      message.attachment?.name,
      message.sender?.name
    ].filter(Boolean).join(" ")).includes(query));
  }, [messageQuery, messages]);

  const threadAttachments = useMemo(
    () => messages.filter((message) => message.attachment),
    [messages]
  );

  const filteredTargets = useMemo(() => {
    const query = normalizeForSearch(targetQuery);
    return targets.filter((target) => !query || normalizeForSearch(`${target.name} ${target.email} ${target.role}`).includes(query));
  }, [targetQuery, targets]);

  async function openConversation(conversation: any) {
    setActive(conversation);
    activeConversationRef.current = conversation;
    setMessages([]);
    setMessageQuery("");
    setMessageSearchOpen(false);
    setDetailsOpen(false);
    setAttachments([]);
    setLoadingThread(true);
    const result = await api.conversation(conversation.id);
    if (result.data) {
      setActive(result.data);
      activeConversationRef.current = result.data;
      setMessages(sortMessages(collectionFrom((result.data as any).messages)));
      loadConversations();
      scrollThreadToBottom(false);
    }
    setLoadingThread(false);
  }

  async function sendCurrentMessage() {
    const value = body.trim();
    if (!active?.id || (!value && !attachments.length)) return;

    const sendingFiles = attachments;
    const tempKey = `local-${Date.now()}`;
    const optimisticMessages = sendingFiles.length
      ? sendingFiles.map((file, index) => ({
          id: `${tempKey}-${index}`,
          body: index === 0 ? value : "",
          mine: true,
          created_at: "Maintenant",
          sender: props.user,
          attachment: {
            name: file.name,
            type: attachmentLabel(file.name, file.mimeType),
            size: file.size ? formatBytes(file.size) : "Fichier"
          }
        }))
      : [{
          id: tempKey,
          body: value,
          mine: true,
          created_at: "Maintenant",
          sender: props.user
        }];

    setMessages((current) => sortMessages([...current, ...optimisticMessages]));
    setBody("");
    setAttachments([]);

    const result = await api.sendMessage(active.id, value, sendingFiles);
    if (result.data) {
      const sentMessages = collectionFrom((result.data as any).messages || result.data);
      setMessages((current) => sortMessages([
        ...current.filter((message) => !String(message.id).startsWith(tempKey)),
        ...sentMessages
      ]));
      loadConversations();
    } else {
      Alert.alert("Message non envoye", result.error || "Impossible d'envoyer le message.");
      setMessages((current) => current.filter((message) => !String(message.id).startsWith(tempKey)));
    }
  }

  async function pickFiles(target: "thread" | "draft") {
    const result = await DocumentPicker.getDocumentAsync({
      multiple: true,
      copyToCacheDirectory: true
    });

    if (result.canceled) return;

    const selected = result.assets || [];
    if (target === "thread") {
      setAttachments((current) => [...current, ...selected].slice(0, 30));
    } else {
      setDraftAttachments((current) => [...current, ...selected].slice(0, 30));
    }
  }

  async function openMessageAttachment(message: any) {
    if (!message.attachment?.id) return;

    if (isImageAttachment(message.attachment)) {
      setPreviewAttachment(message.attachment);
      return;
    }

    const result = await api.openMessageAttachment(message.attachment.id, message.attachment.name);
    if (!result.data) {
      Alert.alert("Piece jointe", result.error || "Impossible d'ouvrir ce fichier.");
    }
  }

  async function createConversation() {
    if (!draft.participant_user_id || !draft.subject.trim() || (!draft.body.trim() && !draftAttachments.length)) {
      Alert.alert("Conversation incomplete", "Choisissez un destinataire, un sujet et un premier message ou fichier.");
      return;
    }

    const result = await api.createConversation({
      ...draft,
      attachments: draftAttachments
    });

    if (result.data) {
      setItems((current) => [result.data, ...current.filter((item) => item.id !== (result.data as any).id)]);
      setNewOpen(false);
      setDraft({ participant_user_id: "", subject: "", body: "", priority: "normal" });
      setDraftAttachments([]);
      openConversation(result.data);
    } else {
      Alert.alert("Conversation", result.error || "Impossible de creer la conversation.");
    }
  }

  if (active) {
    const participants = collectionFrom<any>(active.participants);

    return (
      <View style={styles.chatRoot}>
        <View style={styles.chatHeader}>
          <Tap onPress={() => {
            setActive(null);
            setMessages([]);
          }} style={styles.chatBack} accessibilityLabel="Retour aux conversations">
            <Text style={styles.chatBackText}>‹</Text>
          </Tap>
          <Avatar name={active.name || active.title || "RH"} url={active.avatar_url} size={42} />
          <View style={{ flex: 1 }}>
            <Text style={styles.chatTitle}>{active.name || active.title || "Conversation"}</Text>
            <Text style={styles.chatSubtitle}>{active.subtitle || "RHS Group"} · {active.priority_label || active.priority || "Normal"}</Text>
          </View>
          <Tap onPress={() => setMessageSearchOpen((current) => !current)} style={styles.chatIconButton} accessibilityLabel="Rechercher dans la conversation">
            <Text style={styles.chatIconText}>⌕</Text>
          </Tap>
          <Tap onPress={() => setDetailsOpen(true)} style={styles.chatIconButton} accessibilityLabel="Details de la conversation">
            <Text style={styles.chatIconText}>i</Text>
          </Tap>
          <Tap onPress={() => refreshActiveConversation(true)} style={styles.chatIconButton} accessibilityLabel="Recharger la conversation">
            <Text style={styles.chatIconText}>↻</Text>
          </Tap>
        </View>

        {messageSearchOpen ? (
          <View style={styles.threadSearch}>
            <SearchBar value={messageQuery} onChangeText={setMessageQuery} placeholder="Rechercher un message ou un fichier..." />
            <Text style={styles.searchCount}>{visibleMessages.length} resultat{visibleMessages.length > 1 ? "s" : ""}</Text>
          </View>
        ) : null}

        <ScrollView
          ref={messageScrollRef}
          contentContainerStyle={styles.chatMessages}
          showsVerticalScrollIndicator={false}
          onContentSizeChange={() => scrollThreadToBottom(true)}
        >
          {loadingThread ? <ActivityIndicator color={colors.primary} /> : null}
          {visibleMessages.length ? visibleMessages.map((message) => (
            <ChatBubble key={message.id} mine={message.mine} sender={message.sender?.name} time={message.created_at}>
              {message.body ? <Text style={[styles.bubbleBody, message.mine && styles.bubbleBodyMine]}>{message.body}</Text> : null}
              {message.attachment ? (
                <MessageAttachment
                  attachment={message.attachment}
                  mine={message.mine}
                  token={attachmentToken}
                  onOpen={() => openMessageAttachment(message)}
                />
              ) : null}
            </ChatBubble>
          )) : !loadingThread ? (
            <View style={styles.chatEmpty}>
              <Text style={styles.chatEmptyTitle}>{messageQuery ? "Aucun resultat" : "Aucun message"}</Text>
              <Text style={text.body}>{messageQuery ? "Essayez un autre mot cle." : "Envoyez le premier message de cette conversation."}</Text>
            </View>
          ) : null}
        </ScrollView>

        <View style={styles.chatComposer}>
          {attachments.length ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectedFilesRail}>
              {attachments.map((file, index) => (
                <Tap key={`${file.uri}-${index}`} onPress={() => setAttachments((current) => current.filter((_, itemIndex) => itemIndex !== index))} style={styles.selectedFileChip}>
                  <FileText color={colors.primary} size={15} />
                  <Text style={styles.selectedFileText} numberOfLines={1}>{file.name}</Text>
                  <Text style={styles.selectedFileRemove}>x</Text>
                </Tap>
              ))}
            </ScrollView>
          ) : null}
          <View style={styles.composerRow}>
            <Tap onPress={() => pickFiles("thread")} style={styles.attachButton} accessibilityLabel="Ajouter une piece jointe">
              <Plus color={colors.primary} size={22} />
            </Tap>
            <TextInput
              value={body}
              onChangeText={setBody}
              placeholder={`Ecrire a ${active.name || "RHS"}...`}
              placeholderTextColor="#8b95a7"
              multiline
              style={styles.chatInput}
              onSubmitEditing={sendCurrentMessage}
            />
            <Tap onPress={sendCurrentMessage} style={styles.chatSend} accessibilityLabel="Envoyer le message">
              <Send color="white" size={18} />
            </Tap>
          </View>
          <Text style={styles.chatHelper}>Entree pour envoyer · pieces jointes PDF, DOCX, XLSX, images</Text>
        </View>

        <Sheet visible={detailsOpen} title="Details" onClose={() => setDetailsOpen(false)}>
          <View style={{ gap: 14 }}>
            <Card style={{ gap: 12 }}>
              <Text style={text.h3}>{active.title || active.name}</Text>
              <Text style={text.body}>{active.subtitle || "Conversation RHS"}</Text>
              <View style={styles.progressStats}>
                <Chip>{participants.length || 2} participant{(participants.length || 2) > 1 ? "s" : ""}</Chip>
                <Chip>{threadAttachments.length} fichier{threadAttachments.length > 1 ? "s" : ""}</Chip>
                <Chip>{active.status || "Ouverte"}</Chip>
              </View>
            </Card>
            <Card style={{ gap: 10 }}>
              <Text style={text.h3}>Participants</Text>
              {participants.length ? participants.map((participant) => (
                <View key={participant.id} style={styles.userRow}>
                  <Avatar name={participant.name} url={participant.profile_photo_url} size={38} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.listTitle}>{participant.name}</Text>
                    <Text style={text.small}>{participant.email || participant.role}</Text>
                  </View>
                </View>
              )) : <Text style={text.body}>Participants synchronises avec RHS Hub.</Text>}
            </Card>
            <Card style={{ gap: 10 }}>
              <Text style={text.h3}>Pieces jointes</Text>
              {threadAttachments.length ? threadAttachments.map((message) => (
                <Tap key={message.id} onPress={() => openMessageAttachment(message)} style={styles.attachmentRow}>
                  <FileText color={colors.primary} size={18} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.listTitle} numberOfLines={1}>{message.attachment?.name}</Text>
                    <Text style={text.small}>{message.attachment?.size || message.created_at}</Text>
                  </View>
                </Tap>
              )) : <Text style={text.body}>Aucune piece jointe pour le moment.</Text>}
            </Card>
          </View>
        </Sheet>
        <Sheet visible={!!previewAttachment} title={previewAttachment?.name || "Apercu"} onClose={() => setPreviewAttachment(null)}>
          <View style={{ gap: 14 }}>
            {previewAttachment?.id ? (
              <Image
                source={{
                  uri: api.messageAttachmentUrl(previewAttachment.id),
                  headers: attachmentToken ? { Authorization: `Bearer ${attachmentToken}` } : undefined
                }}
                style={styles.attachmentPreviewImage}
                resizeMode="contain"
              />
            ) : null}
            <Button icon={<FileText color="white" size={16} />} onPress={() => previewAttachment?.id && api.openMessageAttachment(previewAttachment.id, previewAttachment.name)}>
              Telecharger
            </Button>
          </View>
        </Sheet>
      </View>
    );
  }

  return (
    <Screen title="Messages" subtitle="Conversations internes et clients" {...props}>
      <SearchBar value={conversationQuery} onChangeText={setConversationQuery} placeholder="Rechercher une conversation..." />
      <View style={styles.messageToolbar}>
        <Button icon={<Plus color="white" size={16} />} onPress={() => setNewOpen(true)}>Nouvelle</Button>
        <Button variant="outline" onPress={loadConversations}>Actualiser</Button>
      </View>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        {[
          ["all", "Tous", items.length],
          ["unread", "Non lus", items.filter((item) => Number(item.unread_count || 0) > 0).length],
          ["group", "Groupes", items.filter((item) => item.is_group || item.type === "group").length],
          ["urgent", "Urgents", items.filter((item) => item.priority === "urgent").length],
          ["attachments", "Fichiers", items.filter((item) => item.has_attachments).length]
        ].map(([id, label, count]) => (
          <FilterPill
            key={String(id)}
            active={conversationFilter === id}
            label={`${label} ${count}`}
            onPress={() => setConversationFilter(String(id))}
          />
        ))}
      </ScrollView>
      {filteredItems.map((conversation, index) => (
        <FadeIn key={conversation.id || index} delay={index * 45}>
          <Tap onPress={() => openConversation(conversation)} style={[styles.listCard, conversation.unread && styles.listCardUnread]}>
            <Avatar name={conversation.name || conversation.title || "RH"} url={conversation.avatar_url} />
            <View style={{ flex: 1 }}>
              <View style={styles.rowBetween}>
                <Text style={styles.listTitle} numberOfLines={1}>{conversation.name || conversation.title || "Conversation"}</Text>
                <Text style={text.small}>{conversation.time || ""}</Text>
              </View>
              <Text style={text.small} numberOfLines={1}>{conversation.subtitle || "Conversation RHS"}</Text>
              <Text style={styles.previewText} numberOfLines={1}>{conversation.preview || conversation.last_message || "Aucun message recent"}</Text>
              <View style={styles.conversationMeta}>
                <StatusChip status={conversation.priority_label || conversation.priority || "Normal"} />
                {conversation.has_attachments ? <Chip>{conversation.attachments_count} fichier{conversation.attachments_count > 1 ? "s" : ""}</Chip> : null}
              </View>
            </View>
            {conversation.unread_count ? <View style={styles.unreadBadge}><Text style={styles.unreadText}>{conversation.unread_count}</Text></View> : null}
          </Tap>
        </FadeIn>
      ))}
      {!filteredItems.length ? (
        <Card>
          <Text style={text.h3}>Aucune conversation</Text>
          <Text style={[text.body, { marginTop: 8 }]}>Modifiez la recherche ou le filtre pour retrouver vos echanges.</Text>
        </Card>
      ) : null}

      <Sheet visible={newOpen} title="Nouvelle conversation" onClose={() => setNewOpen(false)}>
        <View style={{ gap: 14 }}>
          <SearchBar value={targetQuery} onChangeText={setTargetQuery} placeholder="Rechercher un destinataire..." />
          <View style={styles.targetList}>
            {filteredTargets.slice(0, 8).map((target) => (
              <Tap
                key={target.id}
                onPress={() => setDraft((current) => ({ ...current, participant_user_id: String(target.id) }))}
                style={[styles.targetRow, String(draft.participant_user_id) === String(target.id) && styles.targetRowActive]}
              >
                <Avatar name={target.name} url={target.profile_photo_url} size={40} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.listTitle}>{target.name}</Text>
                  <Text style={text.small}>{target.email || target.role}</Text>
                </View>
              </Tap>
            ))}
          </View>
          <Input label="Sujet" value={draft.subject} onChangeText={(value) => setDraft((current) => ({ ...current, subject: value }))} placeholder="Suivi dossier, urgence client..." />
          <View style={styles.segmented}>
            <Tap onPress={() => setDraft((current) => ({ ...current, priority: "normal" }))} style={[styles.segment, draft.priority === "normal" && styles.segmentActive]}>
              <Text style={[styles.segmentText, draft.priority === "normal" && styles.segmentTextActive]}>Normal</Text>
            </Tap>
            <Tap onPress={() => setDraft((current) => ({ ...current, priority: "urgent" }))} style={[styles.segment, draft.priority === "urgent" && styles.segmentActive]}>
              <Text style={[styles.segmentText, draft.priority === "urgent" && styles.segmentTextActive]}>Urgent</Text>
            </Tap>
          </View>
          <TextInput
            value={draft.body}
            onChangeText={(value) => setDraft((current) => ({ ...current, body: value }))}
            placeholder="Premier message..."
            placeholderTextColor="#8b95a7"
            multiline
            style={[styles.chatInput, { minHeight: 118, textAlignVertical: "top" }]}
          />
          {draftAttachments.length ? (
            <View style={styles.draftFiles}>
              {draftAttachments.map((file, index) => (
                <Tap key={`${file.uri}-${index}`} onPress={() => setDraftAttachments((current) => current.filter((_, itemIndex) => itemIndex !== index))} style={styles.selectedFileChip}>
                  <FileText color={colors.primary} size={15} />
                  <Text style={styles.selectedFileText} numberOfLines={1}>{file.name}</Text>
                  <Text style={styles.selectedFileRemove}>x</Text>
                </Tap>
              ))}
            </View>
          ) : null}
          <View style={styles.messageToolbar}>
            <Button variant="outline" icon={<FileText color={colors.primary} size={16} />} onPress={() => pickFiles("draft")}>Joindre</Button>
            <Button icon={<Send color="white" size={16} />} onPress={createConversation}>Creer</Button>
          </View>
        </View>
      </Sheet>
    </Screen>
  );
}

export function MatchingScreen(props: ScreenProps) {
  const [jobs, setJobs] = useState<any[]>([]);
  const [criterion, setCriterion] = useState<any | null>(null);
  const [activeJob, setActiveJob] = useState<any | null>(null);
  const [matchDetail, setMatchDetail] = useState<any | null>(null);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [openingFile, setOpeningFile] = useState(false);
  const [query, setQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  useEffect(() => {
    api.matchingJobs().then((result) => {
      setJobs(collectionFrom(result.data));
    });
  }, []);

  async function openMatching(job: any) {
    setActiveJob(job);
    setLoadingDetail(true);
    const result = await api.matchingDetails(job.id);
    if (result.data) setActiveJob(result.data);
    setLoadingDetail(false);
  }

  const visibleJobs = useMemo(() => {
    const normalizedQuery = normalizeForSearch(query);

    return jobs.filter((job) => {
      const matchesQuery = !normalizedQuery || normalizeForSearch([
        job.title,
        job.reference,
        job.client,
        job.status
      ].filter(Boolean).join(" ")).includes(normalizedQuery);
      const matchesStatus =
        statusFilter === "all"
        || (statusFilter === "processing" && /cours|processing/i.test(String(job.status || job.raw_status)))
        || (statusFilter === "completed" && /termine|completed/i.test(String(job.status || job.raw_status)))
        || (statusFilter === "selected" && Number(job.selected || 0) > 0);

      return matchesQuery && matchesStatus;
    });
  }, [jobs, query, statusFilter]);

  const visibleMatches = useMemo(() => {
    const matches = activeJob?.matches || [];
    const normalizedQuery = normalizeForSearch(query);

    if (!normalizedQuery) return matches;

    return matches.filter((match: any) => normalizeForSearch([
      match.candidate?.name,
      match.candidate?.email,
      match.candidate?.phone,
      match.candidate?.file,
      match.summary
    ].filter(Boolean).join(" ")).includes(normalizedQuery));
  }, [activeJob?.matches, query]);

  async function openMatchedCv(match: any) {
    const id = match?.candidate?.id;
    if (!id) {
      Alert.alert("CV indisponible", "Ce resultat ne contient pas encore le fichier CV.");
      return;
    }

    setOpeningFile(true);
    const result = await api.openCv(id, match.candidate?.file || `${match.candidate?.name || "cv"}.pdf`);
    setOpeningFile(false);

    if (!result.data && result.error) {
      Alert.alert("Ouverture impossible", result.error);
    }
  }

  return (
    <Screen title="Analyses" subtitle="Matching CV, progression et scores" {...props}>
      <SearchBar value={query} onChangeText={setQuery} placeholder="Filtrer par poste, client ou candidat..." />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        <FilterPill label={`Tous ${jobs.length}`} active={statusFilter === "all"} onPress={() => setStatusFilter("all")} />
        <FilterPill label="En cours" active={statusFilter === "processing"} onPress={() => setStatusFilter("processing")} />
        <FilterPill label="Termines" active={statusFilter === "completed"} onPress={() => setStatusFilter("completed")} />
        <FilterPill label="Selectionnes" active={statusFilter === "selected"} onPress={() => setStatusFilter("selected")} />
      </ScrollView>
      {visibleJobs.map((job, index) => (
        <FadeIn key={job.id || index} delay={index * 80}>
          <Tap onPress={() => openMatching(job)}>
          <Card style={{ gap: 14 }}>
            <View style={styles.rowBetween}>
              <View style={{ flex: 1 }}>
                <Text style={text.h3}>{job.title}</Text>
                <Text style={text.small}>Statut: {job.status} • {job.client}</Text>
              </View>
              <StatusChip status={job.status} />
            </View>
            {job.logo_url ? <Image source={{ uri: assetUrl(job.logo_url) || "" }} style={styles.requestLogo} /> : null}
            <Progress value={job.progress || 0} />
            <View style={styles.progressStats}>
              <Chip>{job.treated || 0} CV traites</Chip>
              <Chip>{job.total || 0} total</Chip>
              <Chip>{job.selected || 0} selectionnes</Chip>
            </View>
            {job.status === "En cours" ? (
              <Button variant="outline" onPress={async () => {
                const result = await api.cancelMatching(job.id);
                if (result.data) {
                  setJobs((current) => current.map((item) => item.id === job.id ? result.data : item));
                }
              }}>
                Annuler le matching
              </Button>
            ) : null}
            <View style={styles.sheetActions}>
              <Button icon={<Target color="white" size={16} />} onPress={() => openMatching(job)}>
                Voir les resultats
              </Button>
            </View>
            <Text style={text.h3}>Scores explicables</Text>
            {(job.criteria || []).map((item: any) => (
              <Tap key={item.name} onPress={() => setCriterion(item)} style={styles.scoreRow}>
                <Text style={{ flex: 1, color: colors.ink, fontWeight: "800" }}>{item.name}</Text>
                <Text style={styles.score}>{item.score}</Text>
              </Tap>
            ))}
          </Card>
          </Tap>
        </FadeIn>
      ))}
      <Sheet visible={!!activeJob} title={activeJob?.title || "Resultats"} onClose={() => setActiveJob(null)}>
        <View style={{ gap: 14 }}>
          {loadingDetail ? <ActivityIndicator color={colors.primary} /> : null}
          <Card style={{ gap: 10 }}>
            <View style={styles.rowBetween}>
              <View style={{ flex: 1 }}>
                <Text style={text.h3}>{activeJob?.reference || "Matching"}</Text>
                <Text style={text.small}>{activeJob?.treated || 0} CV analyses • {activeJob?.selected || 0} selectionnes</Text>
              </View>
              <Text style={styles.bigInlineScore}>{activeJob?.progress || 0}%</Text>
            </View>
            <Progress value={activeJob?.progress || 0} />
          </Card>
          {visibleMatches.map((match: any, index: number) => (
            <FadeIn key={match.id || index} delay={index * 40}>
              <Tap style={styles.matchCard} onPress={() => setMatchDetail(match)}>
                <View style={styles.matchScore}>
                  <Text style={styles.matchScoreText}>{match.score}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.listTitle}>{match.candidate?.name || "Candidat"}</Text>
                  <Text style={text.small}>{match.candidate?.email || match.candidate?.phone || match.candidate?.file || "Coordonnees non renseignees"}</Text>
                  <Text style={styles.previewText} numberOfLines={2}>{match.summary || "Aucun resume disponible."}</Text>
                </View>
                {match.selected ? <StatusChip status="Selectionne" /> : null}
              </Tap>
              <View style={{ marginTop: 8 }}>
                <Button variant="outline" icon={<FileText color={colors.primary} size={16} />} onPress={() => openMatchedCv(match)}>
                  {openingFile ? "Ouverture..." : "Ouvrir le CV"}
                </Button>
              </View>
            </FadeIn>
          ))}
          {!loadingDetail && !(activeJob?.matches || []).length ? <Card><Text style={text.body}>Aucun resultat detaille disponible pour le moment.</Text></Card> : null}
        </View>
      </Sheet>
      <Sheet visible={!!matchDetail} title={matchDetail?.candidate?.name || "Candidat"} onClose={() => setMatchDetail(null)}>
        <View style={{ gap: 14 }}>
          <Text style={styles.bigScore}>{matchDetail?.score}/100</Text>
          <Text style={text.body}>{matchDetail?.summary || "Le resume du matching sera affiche ici des qu'il est disponible."}</Text>
          <Button icon={<FileText color="white" size={16} />} onPress={() => openMatchedCv(matchDetail)}>
            {openingFile ? "Ouverture..." : "Ouvrir le CV"}
          </Button>
          {(matchDetail?.criteria || []).map((item: any) => (
            <Tap key={item.name} onPress={() => setCriterion(item)} style={styles.scoreRow}>
              <Text style={{ flex: 1, color: colors.ink, fontWeight: "800" }}>{item.name}</Text>
              <Text style={styles.score}>{item.score}</Text>
            </Tap>
          ))}
        </View>
      </Sheet>
      <Sheet visible={!!criterion} title={criterion?.name || "Critere"} onClose={() => setCriterion(null)}>
        <View style={{ gap: 14 }}>
          <Text style={styles.bigScore}>{criterion?.score}/25</Text>
          <Text style={text.h3}>Pourquoi ce score ?</Text>
          <Text style={text.body}>{criterion?.why}</Text>
          <Card style={{ backgroundColor: colors.blush }}>
            <Text style={text.h3}>Extrait CV utilise</Text>
            <Text style={[text.body, { marginTop: 8 }]}>{criterion?.evidence}</Text>
          </Card>
        </View>
      </Sheet>
    </Screen>
  );
}

export function RequestsScreen(props: ScreenProps) {
  const [items, setItems] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [activeRequest, setActiveRequest] = useState<any | null>(null);
  const [step, setStep] = useState(0);
  const [draft, setDraft] = useState({
    position_title: "",
    work_location: "",
    candidate_count: "",
    experience_years: "",
    education: "",
    missions: ""
  });

  useEffect(() => {
    api.requests().then((result) => {
      setItems(collectionFrom(result.data));
    });
  }, []);

  async function submitRequest() {
    const count = Number(draft.candidate_count);

    if (!draft.position_title.trim() || !Number.isInteger(count) || count < 1) {
      Alert.alert("Informations manquantes", "Le poste et le nombre de candidats recherches sont obligatoires.");
      return;
    }

    const result = await api.createRequest({
      ...draft,
      candidate_count: count
    });

    if (result.data) {
      setItems((current) => [result.data, ...current]);
      setOpen(false);
      setStep(0);
      setDraft({
        position_title: "",
        work_location: "",
        candidate_count: "",
        experience_years: "",
        education: "",
        missions: ""
      });
      Alert.alert("Demande envoyee", "Votre demande a ete transmise aux equipes RHS GROUP.");
    } else {
      Alert.alert("Erreur", result.error || "Impossible d'envoyer la demande.");
    }
  }

  return (
    <Screen title="Demandes" subtitle="Besoins clients et recrutements" {...props}>
      <View style={styles.rowBetween}>
        <SearchBar placeholder="Rechercher une demande..." />
      </View>
      <Button icon={<Plus color="white" size={16} />} onPress={() => setOpen(true)}>
        Creer une demande
      </Button>
      {items.map((request, index) => (
        <FadeIn key={request.id || index} delay={index * 65}>
          <Tap onPress={async () => {
            setActiveRequest(request);
            const result = await api.requestDetails(request.id);
            if (result.data) setActiveRequest(result.data);
          }}>
          <Card>
            <View style={styles.rowBetween}>
              <View style={{ flex: 1 }}>
                <Text style={text.h3}>{request.title || request.position || request.position_title}</Text>
                <Text style={text.small}>{request.reference || request.request_date || ""}</Text>
              </View>
              <StatusChip status={request.status || "Nouveau"} />
            </View>
            {request.logo_url ? <Image source={{ uri: assetUrl(request.logo_url) || "" }} style={styles.requestLogo} /> : null}
            <Text style={[text.body, { marginTop: 8 }]}>{request.client || request.company || "RHS GROUP"}</Text>
            <View style={styles.progressStats}>
              <Chip>{request.count || request.candidates_count || 0} candidats</Chip>
              <Chip>{request.priority || "Normale"}</Chip>
            </View>
            <View style={[styles.sheetActions, { marginTop: 12 }]}>
              <Button variant="outline" onPress={async () => {
                setActiveRequest(request);
                const result = await api.requestDetails(request.id);
                if (result.data) setActiveRequest(result.data);
              }}>
                Details
              </Button>
              <Button icon={<Target color="white" size={16} />} onPress={() => props.onNavigate?.("matching")}>
                Resultats
              </Button>
            </View>
          </Card>
          </Tap>
        </FadeIn>
      ))}
      <Sheet visible={!!activeRequest} title={activeRequest?.position_title || activeRequest?.title || "Demande"} onClose={() => setActiveRequest(null)}>
        <View style={{ gap: 14 }}>
          {activeRequest?.logo_url ? <Image source={{ uri: assetUrl(activeRequest.logo_url) || "" }} style={styles.detailImage} /> : null}
          <Card style={{ gap: 10 }}>
            <Text style={text.h3}>{activeRequest?.client_name || activeRequest?.client || "RHS GROUP"}</Text>
            <Text style={text.body}>{activeRequest?.admin_notes || activeRequest?.missions || "Les details de cette demande seront synchronises depuis le web app."}</Text>
            <View style={styles.progressStats}>
              <Chip>{activeRequest?.reference || "Sans reference"}</Chip>
              <Chip>{activeRequest?.pipeline_stage || activeRequest?.status || "Nouveau"}</Chip>
              <Chip>{activeRequest?.candidate_count || activeRequest?.count || 0} candidats</Chip>
            </View>
          </Card>
        </View>
      </Sheet>
      <Sheet visible={open} title="Nouvelle demande" onClose={() => setOpen(false)}>
        <View style={{ gap: 14 }}>
          <View style={styles.steps}>
            {["General", "Criteres", "Validation"].map((label, index) => (
              <View key={label} style={[styles.step, step === index && styles.stepActive]}>
                <Text style={[styles.stepText, step === index && { color: colors.primary }]}>{index + 1}. {label}</Text>
              </View>
            ))}
          </View>
          {step === 0 ? (
            <>
              <Input label="Poste" placeholder="Ex: Gestionnaire de produit" value={draft.position_title} onChangeText={(value) => setDraft({ ...draft, position_title: value })} />
              <Input label="Lieu" placeholder="Casablanca" value={draft.work_location} onChangeText={(value) => setDraft({ ...draft, work_location: value })} />
              <Input label="Nombre de candidats recherches" placeholder="Ex: 3" keyboardType="numeric" value={draft.candidate_count} onChangeText={(value) => setDraft({ ...draft, candidate_count: value })} />
            </>
          ) : step === 1 ? (
            <>
              <Input label="Experience" placeholder="2 a 3 ans" value={draft.experience_years} onChangeText={(value) => setDraft({ ...draft, experience_years: value })} />
              <Input label="Formation" placeholder="Bac+3 en logistique" value={draft.education} onChangeText={(value) => setDraft({ ...draft, education: value })} />
              <Input label="Missions" placeholder="Ajoutez les missions" value={draft.missions} onChangeText={(value) => setDraft({ ...draft, missions: value })} />
            </>
          ) : (
            <Card style={{ backgroundColor: colors.blush }}>
              <Text style={text.h3}>Validation</Text>
              <Text style={[text.body, { marginTop: 8 }]}>Verifiez les informations avant envoi. Le nombre de candidats recherches est obligatoire.</Text>
            </Card>
          )}
          <View style={styles.sheetActions}>
            {step > 0 ? <Button variant="outline" onPress={() => setStep(step - 1)}>Precedent</Button> : null}
            <Button onPress={() => step < 2 ? setStep(step + 1) : submitRequest()}>
              {step < 2 ? "Suivant" : "Envoyer"}
            </Button>
          </View>
        </View>
      </Sheet>
    </Screen>
  );
}

export function CvsScreen(props: ScreenProps) {
  const [tab, setTab] = useState<"bank" | "external">("bank");
  const [cvs, setCvs] = useState<any[]>([]);
  const [batches, setBatches] = useState<any[]>([]);
  const [activeCv, setActiveCv] = useState<any | null>(null);
  const [activeBatch, setActiveBatch] = useState<any | null>(null);
  const [loading, setLoading] = useState(false);
  const [openingFile, setOpeningFile] = useState(false);
  const [query, setQuery] = useState("");
  const [cvFilter, setCvFilter] = useState("all");

  const load = () => {
    setLoading(true);
    Promise.all([
      api.cvs(),
      api.externalCvBatches()
    ]).then(([cvResult, externalResult]) => {
      setCvs(collectionFrom(cvResult.data));
      setBatches(collectionFrom(externalResult.data));
    }).finally(() => setLoading(false));
  };

  useEffect(load, []);

  async function openCvFile(cv: any) {
    if (!cv?.id) return;
    setOpeningFile(true);
    const result = await api.openCv(cv.id, cv.filename || `${cv.candidate_name || "cv"}.pdf`);
    setOpeningFile(false);

    if (!result.data && result.error) {
      Alert.alert("Ouverture impossible", result.error);
    }
  }

  async function openExternalFile(file: any) {
    if (!file?.id) return;
    setOpeningFile(true);
    const result = await api.openExternalCv(file.id, file.filename || `${file.candidate_name || "cv-externe"}.pdf`);
    setOpeningFile(false);

    if (!result.data && result.error) {
      Alert.alert("Ouverture impossible", result.error);
    }
  }

  const visibleCvs = useMemo(() => {
    const normalizedQuery = normalizeForSearch(query);

    return cvs.filter((cv) => {
      const matchesQuery = !normalizedQuery || normalizeForSearch([
        cv.candidate_name,
        cv.title,
        cv.current_title,
        cv.email,
        cv.phone,
        cv.city,
        cv.folder,
        cv.source
      ].filter(Boolean).join(" ")).includes(normalizedQuery);
      const matchesFilter =
        cvFilter === "all"
        || (cvFilter === "matched" && Number(cv.matches_count || 0) > 0)
        || (cvFilter === "openable" && cv.can_open !== false);

      return matchesQuery && matchesFilter;
    });
  }, [cvs, cvFilter, query]);

  const visibleBatches = useMemo(() => {
    const normalizedQuery = normalizeForSearch(query);

    return batches.filter((batch) => {
      const matchesQuery = !normalizedQuery || normalizeForSearch([
        batch.name,
        batch.title,
        batch.folder,
        batch.creator,
        batch.status
      ].filter(Boolean).join(" ")).includes(normalizedQuery);
      const matchesFilter =
        cvFilter === "all"
        || (cvFilter === "matched" && Number(batch.indexed_files || 0) > 0)
        || (cvFilter === "openable" && Number(batch.total_files || 0) > 0);

      return matchesQuery && matchesFilter;
    });
  }, [batches, cvFilter, query]);

  return (
    <Screen title="CVs" subtitle="CV Bank et base externe" {...props}>
      <SearchBar value={query} onChangeText={setQuery} placeholder="Filtrer par candidat, ville, source..." />
      <View style={styles.segmented}>
        <Tap onPress={() => setTab("bank")} style={[styles.segment, tab === "bank" && styles.segmentActive]}>
          <Text style={[styles.segmentText, tab === "bank" && styles.segmentTextActive]}>CV Bank</Text>
        </Tap>
        <Tap onPress={() => setTab("external")} style={[styles.segment, tab === "external" && styles.segmentActive]}>
          <Text style={[styles.segmentText, tab === "external" && styles.segmentTextActive]}>Base externe</Text>
        </Tap>
      </View>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
        <FilterPill label="Tous" active={cvFilter === "all"} onPress={() => setCvFilter("all")} />
        <FilterPill label={tab === "bank" ? "Avec match" : "Indexes"} active={cvFilter === "matched"} onPress={() => setCvFilter("matched")} />
        <FilterPill label="Ouvrables" active={cvFilter === "openable"} onPress={() => setCvFilter("openable")} />
      </ScrollView>
      {loading ? <ActivityIndicator color={colors.primary} /> : null}

      {tab === "bank" ? (
        <View style={{ gap: 14 }}>
          {visibleCvs.map((cv, index) => (
            <FadeIn key={cv.id || index} delay={index * 45}>
              <Tap onPress={async () => {
                setActiveCv(cv);
                const result = await api.cvDetails(cv.id);
                if (result.data) setActiveCv(result.data);
              }} style={styles.listCard}>
                <Avatar name={cv.candidate_name || cv.title || "CV"} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.listTitle}>{cv.candidate_name || cv.title}</Text>
                  <Text style={text.small}>{cv.current_title || cv.email || cv.phone || "Profil candidat"}</Text>
                  <Text style={styles.previewText}>{cv.city || cv.folder || cv.source || "CV Bank"}</Text>
                </View>
                <View style={{ alignItems: "flex-end" }}>
                  <Text style={styles.score}>{cv.matches_count || 0}</Text>
                  <Text style={text.small}>matches</Text>
                </View>
              </Tap>
              <View style={{ marginTop: 8 }}>
                <Button variant="outline" onPress={async () => {
                  setActiveCv(cv);
                  const result = await api.cvDetails(cv.id);
                  if (result.data) setActiveCv(result.data);
                }}>
                  Voir details et matchings
                </Button>
              </View>
            </FadeIn>
          ))}
          {!loading && !visibleCvs.length ? <Card><Text style={text.body}>Aucun CV disponible pour ces filtres.</Text></Card> : null}
        </View>
      ) : (
        <View style={{ gap: 14 }}>
          {visibleBatches.map((batch, index) => (
            <FadeIn key={batch.id || index} delay={index * 45}>
              <Tap onPress={async () => {
                setActiveBatch(batch);
                const result = await api.externalCvBatch(batch.id);
                if (result.data) setActiveBatch(result.data);
              }}>
                <Card style={{ gap: 12 }}>
                  <View style={styles.rowBetween}>
                    <View style={{ flex: 1 }}>
                      <Text style={text.h3}>{batch.name || batch.title}</Text>
                      <Text style={text.small}>{batch.folder || batch.creator || batch.created_at || "Lot externe"}</Text>
                    </View>
                    <StatusChip status={batch.status || "En attente"} />
                  </View>
                  <Progress value={batch.progress || 0} />
                  <View style={styles.progressStats}>
                    <Chip>{batch.total_files || 0} fichiers</Chip>
                    <Chip>{batch.indexed_files || 0} indexes</Chip>
                    <Chip>{batch.duplicate_files || 0} doublons</Chip>
                    <Chip>{batch.failed_files || 0} echecs</Chip>
                  </View>
                </Card>
              </Tap>
            </FadeIn>
          ))}
          {!loading && !visibleBatches.length ? <Card><Text style={text.body}>Aucun lot externe pour ces filtres.</Text></Card> : null}
        </View>
      )}

      <Sheet visible={!!activeCv} title={activeCv?.candidate_name || activeCv?.title || "CV"} onClose={() => setActiveCv(null)}>
        <View style={{ gap: 14 }}>
          <Card style={{ gap: 10 }}>
            <Text style={text.h3}>{activeCv?.current_title || "Profil candidat"}</Text>
            <Text style={text.body}>{activeCv?.email || "Email non renseigne"} • {activeCv?.phone || "Telephone non renseigne"}</Text>
            <View style={styles.progressStats}>
              <Chip>{activeCv?.city || "Ville -"}</Chip>
              <Chip>{activeCv?.folder || "Sans dossier"}</Chip>
              <Chip>{activeCv?.source || "Source -"}</Chip>
            </View>
          </Card>
          {activeCv?.can_open !== false ? (
            <Button icon={<FileText color="white" size={16} />} onPress={() => openCvFile(activeCv)}>
              {openingFile ? "Ouverture..." : "Ouvrir le CV"}
            </Button>
          ) : null}
          {(activeCv?.matches || []).map((match: any) => (
            <Card key={match.id} style={{ gap: 8 }}>
              <View style={styles.rowBetween}>
                <Text style={[text.h3, { flex: 1 }]}>{match.request?.title || match.request?.reference || "Matching"}</Text>
                <Text style={styles.score}>{match.score}</Text>
              </View>
              <Text style={text.body}>{match.summary || "Resume non disponible."}</Text>
            </Card>
          ))}
        </View>
      </Sheet>

      <Sheet visible={!!activeBatch} title={activeBatch?.name || activeBatch?.title || "Lot externe"} onClose={() => setActiveBatch(null)}>
        <View style={{ gap: 14 }}>
          <Card style={{ gap: 10 }}>
            <Progress value={activeBatch?.progress || 0} />
            <View style={styles.progressStats}>
              <Chip>{activeBatch?.total_files || 0} fichiers</Chip>
              <Chip>{activeBatch?.indexed_files || 0} indexes</Chip>
              <Chip>{activeBatch?.duplicate_files || 0} doublons</Chip>
              <Chip>{activeBatch?.failed_files || 0} echecs</Chip>
            </View>
          </Card>
          {(activeBatch?.files || []).map((file: any) => (
            <Card key={file.id} style={{ gap: 6 }}>
              <Text style={text.h3}>{file.candidate_name}</Text>
              <Text style={text.small}>{file.current_title || file.filename || "CV externe"}</Text>
              <View style={styles.progressStats}>
                <Chip>{file.status}</Chip>
                {file.duplicate_score ? <Chip>Doublon {Math.round(file.duplicate_score)}%</Chip> : null}
              </View>
              {file.can_open !== false ? (
                <Button variant="outline" onPress={() => openExternalFile(file)}>
                  {openingFile ? "Ouverture..." : "Ouvrir le fichier"}
                </Button>
              ) : null}
            </Card>
          ))}
        </View>
      </Sheet>
    </Screen>
  );
}

export function UsersScreen(props: ScreenProps) {
  const [items, setItems] = useState<any[]>([]);

  useEffect(() => {
    api.users().then((result) => {
      setItems(collectionFrom(result.data));
    });
  }, []);

  return (
    <Screen title="Utilisateurs" subtitle="Comptes, roles et autorisations" {...props}>
      <SearchBar placeholder="Rechercher un utilisateur..." />
      <Button icon={<UserPlus color="white" size={16} />} onPress={() => Alert.alert("Utilisateurs", "La creation mobile arrive ici. Pour l'instant, utilisez l'espace admin web pour creer un compte.")}>Creer un utilisateur</Button>
      {items.map((user, index) => (
        <FadeIn key={user.id || index} delay={index * 65}>
          <Card>
            <View style={styles.userRow}>
              <Avatar name={user.name} url={user.profile_photo_url} />
              <View style={{ flex: 1 }}>
                <Text style={styles.listTitle}>{user.name}</Text>
                <Text style={text.small}>{user.email}</Text>
              </View>
              <StatusChip status={user.online ? "Connecte" : "Hors ligne"} />
            </View>
            <View style={styles.sheetActions}>
              <Button variant="outline" onPress={() => Alert.alert("Utilisateur", "Edition mobile a connecter a l'API utilisateurs.")}>Modifier</Button>
              <Button onPress={() => Alert.alert("Utilisateur", "Autorisation mobile a connecter a l'API utilisateurs.")}>Autoriser</Button>
            </View>
          </Card>
        </FadeIn>
      ))}
    </Screen>
  );
}

export function ResourcesScreen(props: ScreenProps) {
  const [items, setItems] = useState<any[]>([]);
  const [meetings, setMeetings] = useState<any[]>([]);
  const [activeResource, setActiveResource] = useState<any | null>(null);
  const [activeMeeting, setActiveMeeting] = useState<any | null>(null);
  const [createOpen, setCreateOpen] = useState(false);
  const [participants, setParticipants] = useState<any[]>([]);
  const [savingMeeting, setSavingMeeting] = useState(false);
  const [meetingDraft, setMeetingDraft] = useState(() => defaultMeetingDraft(props.user));

  const isAdmin = String(props.user?.role || "").toLowerCase() === "admin";

  const loadMeetings = () => {
    api.meetings().then((result) => {
      setMeetings(collectionFrom(result.data));
    });
  };

  useEffect(() => {
    api.resources().then((result) => {
      setItems(collectionFrom(result.data));
    });
    loadMeetings();
  }, []);

  useEffect(() => {
    if (!createOpen || !isAdmin) return;

    api.users().then((result) => {
      const users = collectionFrom(result.data).filter((item: any) => {
        const role = String(item.role || "").toLowerCase();
        return ["admin", "employee", "supervisor"].includes(role);
      });
      setParticipants(users);
      if (!meetingDraft.participants.length && props.user?.id) {
        setMeetingDraft((current) => ({
          ...current,
          participants: [String(props.user?.id)]
        }));
      }
    });
  }, [createOpen, isAdmin, meetingDraft.participants.length, props.user?.id]);

  async function createMeeting() {
    if (!meetingDraft.title.trim() || !meetingDraft.meeting_date || !meetingDraft.start_time) {
      Alert.alert("Reunion incomplete", "Ajoutez un titre, une date et une heure de debut.");
      return;
    }

    const selectedParticipants = meetingDraft.participants.length
      ? meetingDraft.participants
      : props.user?.id
        ? [String(props.user.id)]
        : [];

    if (!selectedParticipants.length) {
      Alert.alert("Participants", "Selectionnez au moins un participant.");
      return;
    }

    setSavingMeeting(true);
    const result = await api.createMeeting({
      title: meetingDraft.title.trim(),
      description: meetingDraft.description.trim(),
      meeting_date: meetingDraft.meeting_date,
      start_time: meetingDraft.start_time,
      end_time: meetingDraft.end_time,
      location: meetingDraft.location.trim(),
      online_link: meetingDraft.online_link.trim(),
      participants: selectedParticipants
    });
    setSavingMeeting(false);

    if (result.data) {
      setMeetings((current) => [result.data, ...current.filter((meeting) => meeting.id !== result.data.id)]);
      setMeetingDraft(defaultMeetingDraft(props.user));
      setCreateOpen(false);
      Alert.alert("Reunion creee", "La reunion est maintenant disponible dans le planning mobile.");
      loadMeetings();
    } else {
      Alert.alert("Creation impossible", result.error || "Impossible de creer la reunion.");
    }
  }

  function toggleParticipant(id: number | string) {
    const value = String(id);
    setMeetingDraft((current) => ({
      ...current,
      participants: current.participants.includes(value)
        ? current.participants.filter((participant) => participant !== value)
        : [...current.participants, value]
    }));
  }

  return (
    <Screen title="Ressources RH" subtitle="Documents, reunions et support" {...props}>
      <Card>
        <Text style={text.h3}>Code du Travail marocain</Text>
        <Text style={[text.body, { marginTop: 8 }]}>Consultez et telechargez les documents RH partages par RHS GROUP.</Text>
        <View style={{ marginTop: 14 }}>
          <Button
            icon={<FileText color="white" size={16} />}
            onPress={() => {
              if (items[0]) setActiveResource(items[0]);
            }}
          >
            Ouvrir les ressources
          </Button>
        </View>
      </Card>
      {items.length ? items.map((item, index) => (
        <FadeIn key={item.id || index} delay={index * 55}>
          <Tap onPress={async () => {
            setActiveResource(item);
            const result = await api.resourceDetails(item.id);
            if (result.data) setActiveResource(result.data);
          }}>
            <Card>
              <View style={styles.rowBetween}>
                <View style={{ flex: 1 }}>
                  <Text style={text.h3}>{item.title || item.name}</Text>
                  <Text style={text.small}>{item.description || item.category || "Ressource RH"}</Text>
                </View>
                <FileText color={colors.primary} size={22} />
              </View>
            </Card>
          </Tap>
        </FadeIn>
      )) : (
        <Card><Text style={text.body}>Aucune ressource chargee pour le moment.</Text></Card>
      )}
      <View style={styles.rowBetween}>
        <Text style={[text.h3, { marginTop: 8 }]}>Reunions</Text>
        {isAdmin ? (
          <Button icon={<Plus color="white" size={16} />} onPress={() => setCreateOpen(true)}>
            Creer
          </Button>
        ) : null}
      </View>
      {meetings.length ? meetings.map((meeting, index) => (
        <FadeIn key={meeting.id || index} delay={index * 50}>
          <Tap onPress={async () => {
            setActiveMeeting(meeting);
            const result = await api.meetingDetails(meeting.id);
            if (result.data) setActiveMeeting(result.data);
          }}>
            <Card style={{ gap: 8 }}>
              <View style={styles.rowBetween}>
                <Text style={[text.h3, { flex: 1 }]}>{meeting.title || meeting.subject || "Reunion RHS"}</Text>
                <StatusChip status={meeting.status || "Planifiee"} />
              </View>
              <Text style={text.small}>{meeting.meeting_date || meeting.date || ""} {meeting.start_time || ""}</Text>
              <Text style={text.body}>{meeting.location || meeting.online_link || "Lieu non precise"}</Text>
            </Card>
          </Tap>
        </FadeIn>
      )) : <Card><Text style={text.body}>Aucune reunion planifiee.</Text></Card>}
      <Sheet visible={!!activeResource} title={activeResource?.title || activeResource?.name || "Ressource"} onClose={() => setActiveResource(null)}>
        <View style={{ gap: 14 }}>
          <Card>
            <Text style={text.body}>{activeResource?.description || "Document RH partage par RHS GROUP."}</Text>
          </Card>
          {activeResource?.id ? (
            <Button icon={<FileText color="white" size={16} />} onPress={() => Linking.openURL(api.resourceDownloadUrl(activeResource.id))}>
              Telecharger
            </Button>
          ) : null}
        </View>
      </Sheet>
      <Sheet visible={!!activeMeeting} title={activeMeeting?.title || activeMeeting?.subject || "Reunion"} onClose={() => setActiveMeeting(null)}>
        <View style={{ gap: 14 }}>
          <Card style={{ gap: 10 }}>
            <Text style={text.h3}>{activeMeeting?.meeting_date || activeMeeting?.date || "Date a confirmer"}</Text>
            <Text style={text.body}>{activeMeeting?.location || activeMeeting?.online_link || "Lieu non precise"}</Text>
            <Text style={text.small}>{activeMeeting?.description || activeMeeting?.notes || "Details de reunion synchronises avec RHS Hub."}</Text>
          </Card>
          {activeMeeting?.online_link ? <Button onPress={() => Linking.openURL(activeMeeting.online_link)}>Ouvrir le lien</Button> : null}
        </View>
      </Sheet>
      <Sheet visible={createOpen} title="Creer une reunion" onClose={() => setCreateOpen(false)}>
        <View style={{ gap: 14 }}>
          <Card style={{ gap: 12 }}>
            <Input label="Titre" value={meetingDraft.title} onChangeText={(title) => setMeetingDraft((current) => ({ ...current, title }))} placeholder="Point RH hebdomadaire" />
            <Input label="Date" value={meetingDraft.meeting_date} onChangeText={(meeting_date) => setMeetingDraft((current) => ({ ...current, meeting_date }))} placeholder="2026-05-26" />
            <View style={{ flexDirection: "row", gap: 10 }}>
              <View style={{ flex: 1 }}>
                <Input label="Debut" value={meetingDraft.start_time} onChangeText={(start_time) => setMeetingDraft((current) => ({ ...current, start_time }))} placeholder="09:00" />
              </View>
              <View style={{ flex: 1 }}>
                <Input label="Fin" value={meetingDraft.end_time} onChangeText={(end_time) => setMeetingDraft((current) => ({ ...current, end_time }))} placeholder="10:00" />
              </View>
            </View>
            <Input label="Lieu" value={meetingDraft.location} onChangeText={(location) => setMeetingDraft((current) => ({ ...current, location }))} placeholder="Salle RH / Casablanca" />
            <Input label="Lien en ligne" value={meetingDraft.online_link} onChangeText={(online_link) => setMeetingDraft((current) => ({ ...current, online_link }))} placeholder="https://meet.google.com/..." autoCapitalize="none" />
            <Input label="Description" value={meetingDraft.description} onChangeText={(description) => setMeetingDraft((current) => ({ ...current, description }))} placeholder="Objectif de la reunion" multiline />
          </Card>
          <Card style={{ gap: 12 }}>
            <Text style={text.h3}>Participants</Text>
            <View style={styles.participantGrid}>
              {(participants.length ? participants : props.user ? [props.user] : []).map((participant) => {
                const selected = meetingDraft.participants.includes(String(participant.id));
                return (
                  <Tap
                    key={participant.id}
                    onPress={() => toggleParticipant(participant.id)}
                    style={[styles.participantChip, selected && styles.participantChipActive]}
                  >
                    <Text style={[styles.participantText, selected && styles.participantTextActive]} numberOfLines={1}>
                      {participant.name || participant.email}
                    </Text>
                  </Tap>
                );
              })}
            </View>
          </Card>
          <Button icon={<Plus color="white" size={16} />} onPress={createMeeting}>
            {savingMeeting ? "Creation..." : "Creer la reunion"}
          </Button>
        </View>
      </Sheet>
    </Screen>
  );
}

export function ProfileScreen({
  user,
  onLogout,
  onUserUpdate,
  ...props
}: ScreenProps & { user: User | null; onLogout: () => void }) {
  const [name, setName] = useState(user?.name || "");
  const [email, setEmail] = useState(user?.email || "");
  const [photo, setPhoto] = useState<DocumentPicker.DocumentPickerAsset | null>(null);
  const [saving, setSaving] = useState(false);

  async function pickProfilePhoto() {
    const result = await DocumentPicker.getDocumentAsync({
      type: "image/*",
      copyToCacheDirectory: true
    });

    if (!result.canceled) {
      setPhoto(result.assets?.[0] || null);
    }
  }

  async function saveProfile() {
    if (!name.trim() || !email.trim()) {
      Alert.alert("Profil incomplet", "Le nom et l'email sont obligatoires.");
      return;
    }

    setSaving(true);
    const result = await api.updateProfile({
      name: name.trim(),
      email: email.trim(),
      profile_photo: photo || undefined
    });
    setSaving(false);

    const updatedUser = (result.data as any)?.user || result.data;
    if (updatedUser) {
      setPhoto(null);
      onUserUpdate?.(updatedUser as User);
      Alert.alert("Profil mis a jour", "Vos informations ont ete enregistrees.");
    } else {
      Alert.alert("Enregistrement impossible", result.error || "Impossible de mettre a jour le profil.");
    }
  }

  return (
    <Screen title="Profil" subtitle="Parametres du compte" {...props}>
      <Card style={{ alignItems: "center", gap: 12 }}>
        <Avatar name={user?.name || "RHS"} url={user?.profile_photo_url} size={78} />
        <Text style={text.h2}>{user?.name || "Utilisateur RHS"}</Text>
        <Text style={text.body}>{user?.email || "contact@rhsgroup.ma"}</Text>
        <StatusChip status={String(user?.role || "admin")} />
        <Button variant="outline" onPress={pickProfilePhoto}>
          {photo ? "Photo selectionnee" : "Changer la photo"}
        </Button>
      </Card>
      <Card style={{ gap: 12 }}>
        <Input label="Nom" value={name} onChangeText={setName} />
        <Input label="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
        <Input label="Telephone" value={user?.phone || ""} placeholder="05 22 40 08 08" />
        <Button onPress={saveProfile}>{saving ? "Enregistrement..." : "Enregistrer"}</Button>
      </Card>
      <LogoutRow onPress={onLogout} />
    </Screen>
  );
}

export function NotificationsSheet({
  visible,
  onClose
}: {
  visible: boolean;
  onClose: () => void;
}) {
  const [items, setItems] = useState<any[]>([]);

  useEffect(() => {
    if (!visible) return;

    api.notifications().then((result) => {
      const data = result.data as any;
      setItems(collectionFrom(data?.items || data));
    });
  }, [visible]);

  return (
    <Sheet visible={visible} onClose={onClose} title="Notifications">
      <View style={{ gap: 12 }}>
        {items.length ? items.map((item) => (
          <Card key={item.id || item.title} style={{ flexDirection: "row", gap: 12, alignItems: "center" }}>
            <View style={styles.checkCircle}><BellIcon /></View>
            <View style={{ flex: 1 }}>
              <Text style={text.h3}>{item.title}</Text>
              <Text style={text.small}>{item.body || item.message || item.description}</Text>
            </View>
          </Card>
        )) : <Card><Text style={text.body}>Aucune notification.</Text></Card>}
      </View>
    </Sheet>
  );
}

function BellIcon() {
  return <Clock color={colors.primary} size={15} />;
}

function FilterPill({
  active,
  label,
  onPress
}: {
  active: boolean;
  label: string;
  onPress: () => void;
}) {
  return (
    <Tap onPress={onPress} style={[styles.filterPill, active && styles.filterPillActive]}>
      <Text style={[styles.filterPillText, active && styles.filterPillTextActive]}>{label}</Text>
    </Tap>
  );
}

function ChatBubble({ children, mine, sender, time }: React.PropsWithChildren<{ mine?: boolean; sender?: string; time?: string }>) {
  return (
    <View style={[styles.bubble, mine && styles.bubbleMine]}>
      {!mine && sender ? <Text style={styles.bubbleSender}>{sender}</Text> : null}
      {typeof children === "string" ? (
        <Text style={[styles.bubbleBody, mine && styles.bubbleBodyMine]}>{children}</Text>
      ) : children}
      {time ? <Text style={[styles.bubbleTime, mine && { color: "rgba(255,255,255,0.78)" }]}>{time}</Text> : null}
    </View>
  );
}

function MessageAttachment({
  attachment,
  mine,
  token,
  onOpen
}: {
  attachment: any;
  mine?: boolean;
  token?: string | null;
  onOpen: () => void;
}) {
  if (isImageAttachment(attachment)) {
    return (
      <Tap onPress={onOpen} style={[styles.imageAttachment, mine && styles.imageAttachmentMine]}>
        <Image
          source={{
            uri: api.messageAttachmentUrl(attachment.id),
            headers: token ? { Authorization: `Bearer ${token}` } : undefined
          }}
          style={styles.imageAttachmentThumb}
          resizeMode="cover"
        />
        <View style={styles.imageAttachmentFooter}>
          <Text style={[styles.attachmentName, mine && { color: "white" }]} numberOfLines={1}>
            {attachment.name || "Image"}
          </Text>
          <Text style={[styles.attachmentMeta, mine && { color: "rgba(255,255,255,0.78)" }]}>
            {attachment.size || "Toucher pour inspecter"}
          </Text>
        </View>
      </Tap>
    );
  }

  return (
    <Tap onPress={onOpen} style={[styles.attachmentBubble, mine && styles.attachmentBubbleMine]}>
      <View style={styles.attachmentIcon}>
        <Text style={styles.attachmentIconText}>{String(attachment.type || "DOC").slice(0, 4)}</Text>
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.attachmentName, mine && { color: "white" }]} numberOfLines={2}>{attachment.name || "Piece jointe"}</Text>
        <Text style={[styles.attachmentMeta, mine && { color: "rgba(255,255,255,0.78)" }]}>{attachment.size || attachment.type || "Ouvrir"}</Text>
      </View>
    </Tap>
  );
}

function normalizeForSearch(value: string) {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

function formatBytes(bytes: number) {
  if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1).replace(".", ",")} Mo`;
  return `${Math.max(1, Math.round(bytes / 1024))} Ko`;
}

function sortMessages(items: any[]) {
  return [...items].sort((a, b) => {
    const aTime = Date.parse(a.created_at_iso || a.created_at || "") || Number(a.id || 0);
    const bTime = Date.parse(b.created_at_iso || b.created_at || "") || Number(b.id || 0);

    return aTime - bTime;
  });
}

function isImageAttachment(attachment?: any | null) {
  return Boolean(
    attachment?.is_image
    || String(attachment?.mime || "").startsWith("image/")
    || /\.(png|jpe?g|gif|webp|bmp|heic)$/i.test(String(attachment?.name || ""))
  );
}

function attachmentLabel(name?: string | null, mime?: string | null) {
  if (mime?.startsWith("image/")) return "Image";
  if (mime === "application/pdf") return "PDF";
  const extension = (name || "").split(".").pop()?.toUpperCase();
  return extension || "DOC";
}

function Progress({ value }: { value: number }) {
  const progress = useRef(new Animated.Value(0)).current;
  const clampedValue = Math.max(0, Math.min(value, 100));

  useEffect(() => {
    Animated.timing(progress, {
      toValue: clampedValue,
      duration: 720,
      easing: premiumEase,
      useNativeDriver: false
    }).start();
  }, [clampedValue, progress]);

  const width = progress.interpolate({
    inputRange: [0, 100],
    outputRange: ["0%", "100%"]
  });

  return (
    <View style={styles.progressTrack}>
      <Animated.View style={[styles.progressFill, { width }]} />
    </View>
  );
}

function Chip({ children }: React.PropsWithChildren) {
  return (
    <View style={styles.chip}>
      <View style={styles.chipDot} />
      <Text style={styles.chipText}>{children}</Text>
    </View>
  );
}

function StatLine({ label, value }: { label: string; value: number | string }) {
  return (
    <View style={styles.statLine}>
      <Text style={styles.statLineLabel}>{label}</Text>
      <Text style={styles.statLineValue}>{Number(value || 0).toLocaleString("fr-FR")}</Text>
    </View>
  );
}

function StatusChip({ status }: { status: string }) {
  const isGood = /termine|connecte|valide|success/i.test(status);
  return (
    <View style={[styles.status, isGood && { backgroundColor: "#dcfce7" }]}>
      <Text style={[styles.statusText, isGood && { color: colors.success }]}>{status}</Text>
    </View>
  );
}

function Avatar({ name, url, size = 48 }: { name: string; url?: string | null; size?: number }) {
  const source = assetUrl(url);
  const shape = {
    width: size,
    height: size,
    borderRadius: Math.max(16, Math.round(size * 0.38))
  };

  if (source) {
    return (
      <Image
        source={{ uri: source }}
        style={[styles.avatar, shape]}
      />
    );
  }

  return (
    <View style={[styles.avatar, shape]}>
      <Text style={[styles.avatarText, size > 60 && { fontSize: 20 }]}>{initials(name)}</Text>
    </View>
  );
}

function initials(name: string) {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("") || "RH";
}

function defaultMeetingDraft(user?: User | null) {
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(now.getDate() + 1);

  return {
    title: "",
    description: "",
    meeting_date: formatDateInput(tomorrow),
    start_time: "09:00",
    end_time: "10:00",
    location: "",
    online_link: "",
    participants: user?.id ? [String(user.id)] : []
  };
}

function formatDateInput(date: Date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

type ScreenProps = {
  onProfile?: () => void;
  onNotifications?: () => void;
  onNavigate?: (screen: string) => void;
  onTabsHiddenChange?: (hidden: boolean) => void;
  onUserUpdate?: (user: User) => void;
  user?: User | null;
};

type FeatureItem = {
  key: string;
  target: string;
  label: string;
  value: string;
  description: string;
};

function hasPermission(user: User | null, permission: string) {
  if (!user) return false;
  const role = String(user.role || "").toLowerCase();
  return role === "admin" || (user.permissions || []).includes(permission);
}

function canUseFeature(user: User | null, feature: string) {
  const role = String(user?.role || "").toLowerCase();
  if (role === "admin") return true;
  if (feature === "dashboard" || feature === "messages" || feature === "resources" || feature === "profile") return true;
  if (feature === "requests") return role === "client" || hasPermission(user, "recruitment_requests") || hasPermission(user, "recruitment_assignments_view");
  if (feature === "matching") return role !== "client" || hasPermission(user, "recruitment_requests");
  if (feature === "cvs") return hasPermission(user, "cv_bank") || hasPermission(user, "external_cvs");
  if (feature === "users") return role === "admin";
  return false;
}

function featureCatalog(user: User | null, stats: any, metricItems: any[]): FeatureItem[] {
  const messages = metricItems.find((item) => item.label === "Messages")?.value || 0;
  const items: FeatureItem[] = [
    { key: "requests", target: "requests", label: "Demandes", value: `${stats.requests?.pending || 0} en attente`, description: "Creation, details et suivi client" },
    { key: "matching", target: "matching", label: "Resultats matching", value: `${stats.matching?.completed || 0} termines`, description: "Scores, candidats et criteres" },
    { key: "messages", target: "messages", label: "Conversations", value: `${messages} fils`, description: "Messages internes et clients" },
    { key: "resources", target: "resources", label: "Reunions", value: `${stats.planning?.upcoming || 0} a venir`, description: "Planning, liens et ressources RH" },
    { key: "cvs-bank", target: "cvs", label: "CV Bank", value: `${stats.library?.cv_bank || 0} profils`, description: "CV, dossiers et historiques" },
    { key: "external-cvs", target: "cvs", label: "Base externe", value: `${stats.library?.external_batches || 0} lots`, description: "Imports, doublons et indexation" },
    { key: "users", target: "users", label: "Utilisateurs", value: "Roles", description: "Comptes et autorisations" },
  ];

  return items.filter((item) => canUseFeature(user, item.target));
}

function primaryTabsFor(user: User | null): [string, string][] {
  const role = String(user?.role || "").toLowerCase();
  const candidates: [string, string][] = role === "client"
    ? [["dashboard", "Tableau"], ["requests", "Demandes"], ["messages", "Messages"], ["resources", "RH"], ["profile", "Profil"]]
    : [["dashboard", "Tableau"], ["requests", "Demandes"], ["messages", "Messages"], ["matching", "Analyses"], ["cvs", "CVs"], ["resources", "Planning"], ["users", "Users"], ["profile", "Profil"]];

  return candidates.filter(([target]) => canUseFeature(user, target));
}

export function AppShell({
  user,
  onLogout,
  onUserUpdate
}: {
  user: User | null;
  onLogout: () => void;
  onUserUpdate?: (user: User) => void;
}) {
  const role = String(user?.role || "admin").toLowerCase();
  const [active, setActive] = useState("dashboard");
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [tabsHidden, setTabsHidden] = useState(false);
  const transition = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    transition.setValue(0);
    Animated.timing(transition, {
      toValue: 1,
      duration: 360,
      easing: premiumEase,
      useNativeDriver: true
    }).start();
  }, [active, transition]);

  const [visitedScreens, setVisitedScreens] = useState<string[]>([active]);

  useEffect(() => {
    setVisitedScreens((current) => current.includes(active) ? current : [...current, active]);
  }, [active]);

  const common = useMemo(
    () => ({
      onNotifications: () => setNotificationsOpen(true),
      onProfile: () => setActive("profile"),
      onNavigate: (screen: string) => setActive(screen),
      onTabsHiddenChange: setTabsHidden,
      onUserUpdate,
      user
    }),
    [onUserUpdate, user]
  );
  const primaryTabs = useMemo(() => primaryTabsFor(user), [user]);
  useLiveNotifications({
    enabled: Boolean(user),
    user,
    onNavigate: setActive
  });

  const screenStyle = {
    flex: 1,
    opacity: transition,
    transform: [
      {
        translateX: transition.interpolate({
          inputRange: [0, 1],
          outputRange: [28, 0]
        })
      },
      {
        scale: transition.interpolate({
          inputRange: [0, 1],
          outputRange: [0.985, 1]
        })
      }
    ]
  };

  return (
    <View style={{ flex: 1 }}>
      <Animated.View style={screenStyle}>
        {visitedScreens.includes("dashboard") ? <View style={[styles.screenSlot, active !== "dashboard" && styles.screenSlotHidden]}><DashboardScreen {...common} /></View> : null}
        {visitedScreens.includes("messages") ? <View style={[styles.screenSlot, active !== "messages" && styles.screenSlotHidden]}><MessagesScreen {...common} /></View> : null}
        {visitedScreens.includes("matching") ? <View style={[styles.screenSlot, active !== "matching" && styles.screenSlotHidden]}><MatchingScreen {...common} /></View> : null}
        {visitedScreens.includes("cvs") ? <View style={[styles.screenSlot, active !== "cvs" && styles.screenSlotHidden]}><CvsScreen {...common} /></View> : null}
        {visitedScreens.includes("users") ? <View style={[styles.screenSlot, active !== "users" && styles.screenSlotHidden]}><UsersScreen {...common} /></View> : null}
        {visitedScreens.includes("requests") ? <View style={[styles.screenSlot, active !== "requests" && styles.screenSlotHidden]}><RequestsScreen {...common} /></View> : null}
        {visitedScreens.includes("resources") ? <View style={[styles.screenSlot, active !== "resources" && styles.screenSlotHidden]}><ResourcesScreen {...common} /></View> : null}
        {visitedScreens.includes("profile") ? <View style={[styles.screenSlot, active !== "profile" && styles.screenSlotHidden]}><ProfileScreen onLogout={onLogout} {...common} /></View> : null}
      </Animated.View>
      {!tabsHidden ? <BottomTabs active={active} setActive={setActive} role={role} tabs={primaryTabs} /> : null}
      <NotificationsSheet visible={notificationsOpen} onClose={() => setNotificationsOpen(false)} />
    </View>
  );
}

const styles = StyleSheet.create({
  screenSlot: { flex: 1 },
  screenSlotHidden: { display: "none" },
  loginRoot: { flex: 1, justifyContent: "center", padding: 20 },
  loginHalo: {
    position: "absolute",
    width: 260,
    height: 260,
    borderRadius: 999,
    borderWidth: 2,
    borderColor: "rgba(255,255,255,0.18)",
    right: -40,
    top: 90
  },
  loginCard: {
    backgroundColor: "rgba(255,255,255,0.96)",
    borderRadius: radius.lg,
    padding: 22,
    ...shadow
  },
  loginLogo: {
    width: 58,
    height: 58,
    borderRadius: radius.lg,
    backgroundColor: colors.primary,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 16
  },
  loginLogoText: { color: "white", fontWeight: "900", fontSize: 17 },
  loginLogoImage: { width: 46, height: 46 },
  error: { color: colors.primary, fontWeight: "800" },
  heroCard: { borderRadius: radius.lg, padding: 22, overflow: "hidden", ...shadow },
  heroActions: { marginTop: 18, alignSelf: "flex-start" },
  metricsGrid: { flexDirection: "row", flexWrap: "wrap", gap: 12 },
  metricCell: { width: "48%" },
  moduleList: { gap: 10 },
  moduleTile: {
    width: "100%",
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "white",
    padding: 14,
    minHeight: 76,
    flexDirection: "row",
    alignItems: "center",
    gap: 12
  },
  moduleIcon: {
    width: 44,
    height: 44,
    borderRadius: radius.lg,
    backgroundColor: colors.blush,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: colors.line
  },
  moduleIconText: { color: colors.primary, fontWeight: "900", fontSize: 16 },
  moduleValue: { color: colors.primary, fontWeight: "900", fontSize: 13, maxWidth: 92, textAlign: "right" },
  moduleLabel: { color: colors.ink, fontWeight: "900", fontSize: 15 },
  moduleHint: { color: colors.muted, fontWeight: "700", fontSize: 12, lineHeight: 16, marginTop: 3 },
  statRows: { gap: 10 },
  statLine: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    borderBottomWidth: 1,
    borderBottomColor: colors.line,
    paddingBottom: 9
  },
  statLineLabel: { color: colors.muted, fontWeight: "800", flex: 1 },
  statLineValue: { color: colors.ink, fontWeight: "900", fontSize: 16 },
  metricIcon: {
    width: 42,
    height: 42,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 12
  },
  metricValue: { color: colors.ink, fontSize: 28, fontWeight: "900" },
  activityRow: { flexDirection: "row", alignItems: "center", gap: 10, marginTop: 14 },
  checkCircle: {
    width: 25,
    height: 25,
    borderRadius: 99,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center"
  },
  activityText: { color: colors.ink, fontWeight: "700", flex: 1 },
  listCard: {
    minHeight: 76,
    backgroundColor: "white",
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    padding: 14,
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    ...shadow
  },
  listCardUnread: {
    borderColor: "#ffaaa3",
    backgroundColor: colors.blush
  },
  previewText: {
    color: colors.ink,
    fontSize: 12,
    lineHeight: 17,
    fontWeight: "700",
    marginTop: 3
  },
  unreadBadge: {
    minWidth: 22,
    height: 22,
    borderRadius: 999,
    backgroundColor: colors.primary,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 6
  },
  unreadText: { color: "white", fontSize: 11, fontWeight: "900" },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: radius.lg,
    backgroundColor: colors.primary,
    alignItems: "center",
    justifyContent: "center"
  },
  avatarText: { color: "white", fontWeight: "900" },
  listTitle: { color: colors.ink, fontWeight: "900", fontSize: 15 },
  bubble: {
    alignSelf: "flex-start",
    maxWidth: "80%",
    backgroundColor: "white",
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.lg,
    borderBottomLeftRadius: 6,
    paddingHorizontal: 13,
    paddingVertical: 10
  },
  bubbleMine: {
    alignSelf: "flex-end",
    backgroundColor: colors.primary,
    borderColor: colors.primary,
    borderBottomLeftRadius: radius.lg,
    borderBottomRightRadius: 6
  },
  bubbleSender: { color: colors.primary, fontSize: 11, fontWeight: "900", marginBottom: 5 },
  bubbleTime: { color: colors.muted, fontSize: 10, fontWeight: "800", marginTop: 7 },
  rowBetween: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 12 },
  status: { backgroundColor: colors.primarySoft, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  statusText: { color: colors.primary, fontSize: 11, fontWeight: "900" },
  progressTrack: { height: 10, borderRadius: 999, backgroundColor: "#eceff4", overflow: "hidden" },
  progressFill: { height: "100%", borderRadius: 999, backgroundColor: colors.primary },
  progressStats: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginTop: 6 },
  chip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 7,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: "white",
    borderWidth: 1,
    borderColor: colors.line
  },
  chipDot: { width: 10, height: 10, borderRadius: 99, backgroundColor: colors.primarySoft },
  chipText: { color: colors.navy, fontSize: 12, fontWeight: "800" },
  scoreRow: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: colors.blush,
    borderRadius: radius.lg,
    padding: 13,
    gap: 10
  },
  score: { color: colors.primary, fontWeight: "900", fontSize: 17 },
  bigScore: { color: colors.primary, fontSize: 54, fontWeight: "900" },
  bigInlineScore: { color: colors.primary, fontSize: 28, fontWeight: "900" },
  segmented: {
    flexDirection: "row",
    gap: 8,
    backgroundColor: "#f5f6fa",
    borderRadius: radius.lg,
    padding: 5
  },
  segment: {
    flex: 1,
    borderRadius: radius.lg,
    paddingVertical: 11,
    alignItems: "center"
  },
  segmentActive: {
    backgroundColor: "white",
    borderWidth: 1,
    borderColor: "#ffb7af",
    ...shadow
  },
  segmentText: { color: colors.muted, fontWeight: "900" },
  segmentTextActive: { color: colors.primary },
  requestLogo: {
    width: "100%",
    height: 118,
    borderRadius: radius.lg,
    backgroundColor: colors.blush,
    marginTop: 10
  },
  detailImage: {
    width: "100%",
    height: 170,
    borderRadius: radius.lg,
    backgroundColor: colors.blush
  },
  matchCard: {
    minHeight: 92,
    backgroundColor: "white",
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    padding: 14,
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    ...shadow
  },
  matchScore: {
    width: 54,
    height: 54,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center"
  },
  matchScoreText: { color: colors.primary, fontWeight: "900", fontSize: 18 },
  steps: { flexDirection: "row", gap: 8 },
  step: { flex: 1, borderRadius: 999, borderWidth: 1, borderColor: colors.line, padding: 10 },
  stepActive: { borderColor: "#ffaaa3", backgroundColor: colors.blush },
  stepText: { color: colors.muted, fontWeight: "900", fontSize: 12 },
  sheetActions: { flexDirection: "row", justifyContent: "flex-end", alignItems: "center", gap: 10, flexWrap: "wrap" },
  messageToolbar: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", gap: 10, flexWrap: "wrap" },
  filterRail: { gap: 8, paddingRight: 18 },
  filterPill: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "white",
    paddingHorizontal: 13,
    paddingVertical: 9
  },
  filterPillActive: {
    borderColor: "#ff9d96",
    backgroundColor: colors.primarySoft
  },
  filterPillText: { color: colors.muted, fontWeight: "900", fontSize: 12 },
  filterPillTextActive: { color: colors.primary },
  conversationMeta: { flexDirection: "row", gap: 8, flexWrap: "wrap", marginTop: 9 },
  userRow: { flexDirection: "row", alignItems: "center", gap: 12 },
  participantGrid: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  participantChip: {
    width: "100%",
    minHeight: 48,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "white",
    paddingHorizontal: 12,
    paddingVertical: 9,
    justifyContent: "center"
  },
  participantChipActive: {
    borderColor: colors.primary,
    backgroundColor: colors.primarySoft
  },
  participantText: { color: colors.muted, fontSize: 12, fontWeight: "900" },
  participantTextActive: { color: colors.primary },
  chatRoot: { flex: 1, backgroundColor: colors.bg },
  chatHeader: {
    minHeight: 70,
    paddingHorizontal: 10,
    paddingVertical: 10,
    backgroundColor: "white",
    borderBottomWidth: 1,
    borderBottomColor: colors.line,
    flexDirection: "row",
    alignItems: "center",
    gap: 10
  },
  chatBack: {
    width: 30,
    height: 42,
    alignItems: "center",
    justifyContent: "center"
  },
  chatBackText: { color: colors.ink, fontWeight: "900", fontSize: 34, lineHeight: 38 },
  chatTitle: { color: colors.ink, fontWeight: "900", fontSize: 17 },
  chatSubtitle: { color: colors.muted, fontWeight: "700", fontSize: 12, marginTop: 2 },
  chatIconButton: {
    width: 36,
    height: 36,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center"
  },
  chatIconText: { color: colors.primary, fontSize: 18, fontWeight: "900" },
  threadSearch: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: "white",
    borderBottomWidth: 1,
    borderBottomColor: colors.line,
    gap: 8
  },
  searchCount: { color: colors.muted, fontSize: 12, fontWeight: "800", textAlign: "right" },
  chatMessages: {
    paddingHorizontal: 14,
    paddingTop: 18,
    paddingBottom: 138,
    gap: 10
  },
  chatEmpty: {
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
    marginTop: 60
  },
  chatEmptyTitle: { color: colors.ink, fontWeight: "900", fontSize: 18, marginBottom: 8 },
  bubbleBody: { color: colors.ink, fontWeight: "800", lineHeight: 19 },
  bubbleBodyMine: { color: "white" },
  attachmentBubble: {
    minWidth: 210,
    marginTop: 4,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "#f8fafc",
    padding: 10,
    flexDirection: "row",
    alignItems: "center",
    gap: 10
  },
  attachmentBubbleMine: {
    backgroundColor: "rgba(255,255,255,0.14)",
    borderColor: "rgba(255,255,255,0.24)"
  },
  attachmentIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center"
  },
  attachmentIconText: { color: colors.primary, fontSize: 10, fontWeight: "900" },
  attachmentName: { color: colors.ink, fontSize: 13, fontWeight: "900" },
  attachmentMeta: { color: colors.muted, fontSize: 11, fontWeight: "800", marginTop: 3 },
  imageAttachment: {
    minWidth: 230,
    maxWidth: 280,
    marginTop: 6,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "#f8fafc",
    overflow: "hidden"
  },
  imageAttachmentMine: {
    backgroundColor: "rgba(255,255,255,0.14)",
    borderColor: "rgba(255,255,255,0.24)"
  },
  imageAttachmentThumb: {
    width: "100%",
    height: 190,
    backgroundColor: colors.primarySoft
  },
  imageAttachmentFooter: {
    paddingHorizontal: 10,
    paddingVertical: 9
  },
  attachmentPreviewImage: {
    width: "100%",
    height: 420,
    borderRadius: radius.lg,
    backgroundColor: "white"
  },
  chatComposer: {
    position: "absolute",
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: 14,
    paddingTop: 10,
    paddingBottom: 16,
    backgroundColor: colors.glass,
    borderTopWidth: 1,
    borderTopColor: colors.line,
    gap: 8
  },
  selectedFilesRail: { maxHeight: 44 },
  selectedFileChip: {
    height: 38,
    maxWidth: 210,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: "#ffb7af",
    backgroundColor: "white",
    paddingHorizontal: 10,
    flexDirection: "row",
    alignItems: "center",
    gap: 7,
    marginRight: 8
  },
  selectedFileText: { color: colors.ink, fontSize: 12, fontWeight: "800", maxWidth: 138 },
  selectedFileRemove: { color: colors.primary, fontWeight: "900" },
  composerRow: {
    flexDirection: "row",
    alignItems: "flex-end",
    gap: 10
  },
  attachButton: {
    width: 48,
    height: 48,
    borderRadius: radius.lg,
    backgroundColor: colors.primarySoft,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: "#ffc7c0"
  },
  chatInput: {
    flex: 1,
    maxHeight: 112,
    minHeight: 46,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "white",
    color: colors.ink,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontWeight: "700"
  },
  chatHelper: { color: colors.muted, fontSize: 10, fontWeight: "700", paddingLeft: 58 },
  chatSend: {
    width: 48,
    height: 48,
    borderRadius: radius.lg,
    backgroundColor: colors.primary,
    alignItems: "center",
    justifyContent: "center",
    ...shadow
  },
  attachmentRow: {
    minHeight: 54,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    padding: 12,
    flexDirection: "row",
    alignItems: "center",
    gap: 10
  },
  targetList: { gap: 8, maxHeight: 250 },
  targetRow: {
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: "white",
    padding: 10,
    flexDirection: "row",
    alignItems: "center",
    gap: 10
  },
  targetRowActive: {
    borderColor: "#ff9d96",
    backgroundColor: colors.primarySoft
  },
  draftFiles: { gap: 8 }
});
