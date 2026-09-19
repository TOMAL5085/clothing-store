import { useState, type FormEvent } from "react";
import { Link, Navigate, useLocation, useNavigate } from "react-router-dom";
import { LogIn } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, Field, Input } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { ApiError } from "@/lib/api";

export default function LoginPage() {
  usePageTitle("Sign In");
  const navigate = useNavigate();
  const location = useLocation();
  const { user, login, status } = useAuthStore();
  const pushToast = useUiStore((s) => s.pushToast);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [errors, setErrors] = useState<{ email?: string; password?: string }>({});

  const from = (location.state as { from?: string } | null)?.from ?? "/account";

  if (user) return <Navigate to={from} replace />;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    const next: typeof errors = {};
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) next.email = "Enter a valid email address.";
    if (password.length < 6) next.password = "Password must be at least 6 characters.";
    setErrors(next);
    if (Object.keys(next).length) return;
    try {
      await login(email, password);
      pushToast("Welcome back to JAAJ");
      navigate(from, { replace: true });
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors({
          email: error.errors?.email?.[0] ?? error.message,
          password: error.errors?.password?.[0],
        });
        return;
      }
      setErrors({ email: "Sign in failed. Please try again." });
    }
  };

  return (
    <div className="max-w-md">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Members</p>
      <h1 className="mt-3 font-display text-4xl text-ink dark:text-linen">Welcome back</h1>
      <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
        Sign in for faster checkout, order tracking and your saved pieces.
      </p>

      <form onSubmit={submit} noValidate className="mt-8 space-y-5">
        <Field label="Email" htmlFor="login-email" required error={errors.email}>
          <Input
            id="login-email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(e) => { setEmail(e.target.value); setErrors((p) => ({ ...p, email: undefined })); }}
            aria-invalid={Boolean(errors.email)}
          />
        </Field>
        <Field label="Password" htmlFor="login-password" required error={errors.password}>
          <Input
            id="login-password"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(e) => { setPassword(e.target.value); setErrors((p) => ({ ...p, password: undefined })); }}
            aria-invalid={Boolean(errors.password)}
          />
        </Field>
        <div className="flex items-center justify-between">
          <label className="flex cursor-pointer items-center gap-2.5 text-sm text-smoke dark:text-linen-dim">
            <input type="checkbox" defaultChecked className="h-4 w-4 accent-bronze" />
            Remember me
          </label>
          <Link
            to="/forgot-password"
            className="text-xs font-bold tracking-[0.1em] uppercase text-bronze underline-offset-4 hover:underline"
          >
            Forgot password?
          </Link>
        </div>
        <Button type="submit" size="lg" className="w-full" icon={LogIn} loading={status === "loading"}>
          {status === "loading" ? "Signing In…" : "Sign In"}
        </Button>
      </form>

      <p className="mt-6 border-t border-line pt-6 text-sm text-smoke dark:border-line-dark dark:text-linen-dim">
        New to JAAJ?{" "}
        <Link to="/register" className="font-bold text-ink underline-offset-4 hover:text-bronze hover:underline dark:text-linen">
          Create an account
        </Link>
      </p>
    </div>
  );
}


