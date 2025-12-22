import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import type { User } from "../../entities/user";
import { fetchMe, login as apiLogin, register as apiRegister } from "../../api/auth";

const ACCESS_TOKEN_KEY = "parking.token";
const REFRESH_TOKEN_KEY = "parking.refreshToken";

interface AuthState {
  user: User | null;
  token: string | null;
  isReady: boolean;
}

interface AuthContextValue extends AuthState {
  login: (email: string, password: string) => Promise<void>;
  register: (payload: {
    email: string;
    password: string;
    first_name: string;
    last_name: string;
    role: "client" | "proprietor";
  }) => Promise<void>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<AuthState>({
    user: null,
    token: null,
    isReady: false,
  });

  useEffect(() => {
    const token = localStorage.getItem(ACCESS_TOKEN_KEY);
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);
    if (!token && !refreshToken) {
      setState((prev) => ({ ...prev, isReady: true }));
      return;
    }

    setState((prev) => ({ ...prev, token }));
    fetchMe()
      .then((user) => {
        setState({ user, token: localStorage.getItem(ACCESS_TOKEN_KEY), isReady: true });
      })
      .catch(() => {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        localStorage.removeItem(REFRESH_TOKEN_KEY);
        setState({ user: null, token: null, isReady: true });
      });
  }, []);

  const login = async (email: string, password: string) => {
    const response = await apiLogin({ email, password });
    const accessToken = response.token ?? response.access_token ?? null;
    const refreshToken = response.refresh_token ?? null;
    if (accessToken) {
      localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
    }
    if (refreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    }
    setState({ user: response.user, token: accessToken, isReady: true });
  };

  const register = async (payload: {
    email: string;
    password: string;
    first_name: string;
    last_name: string;
    role: "client" | "proprietor";
  }) => {
    const response = await apiRegister(payload);
    const accessToken = response.token ?? response.access_token ?? null;
    const refreshToken = response.refresh_token ?? null;
    if (accessToken) {
      localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
    }
    if (refreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    }
    setState({ user: response.user, token: accessToken, isReady: true });
  };

  const logout = () => {
    localStorage.removeItem(ACCESS_TOKEN_KEY);
    localStorage.removeItem(REFRESH_TOKEN_KEY);
    setState({ user: null, token: null, isReady: true });
  };

  const value = useMemo(
    () => ({
      ...state,
      login,
      register,
      logout,
    }),
    [state]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
}
