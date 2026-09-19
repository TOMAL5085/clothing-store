import { useState, type FormEvent } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";
import { UserPlus } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, Field, Input } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { ApiError } from "@/lib/api";

export default function RegisterPage() {
  usePageTitle("Create Account");
  const navigate = useNavigate();
  const { user, register, status } = useAuthStore();
  const pushToast = useUiStore((s) => s.pushToast);

  const [form, setForm] = useState({ name: "", email: "", phone: "", password: "", confirm: "", terms: false });
  const [errors, setErrors] = useState<Record<string, string>>({});

  if (user) return <Navigate to="/account" replace />;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    const next: Record<string, string> = {};
    if (form.name.trim().length < 2) next.name = "Tell us your name.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(form.email)) next.email = "Enter a valid email address.";
    if (form.password.length < 8) next.password = "Use at least 8 characters.";
    if (form.confirm !== form.password) next.confirm = "Passwords don't match.";
    if (!form.terms) next.terms = "Please accept the terms to continue.";
    setErrors(next);
    if (Object.keys(next).length) return;
    try {
      const debugOtp = await register(form.name, form.email, form.password, form.phone || undefined);
      pushToast(debugOtp ? `Verification code: ${debugOtp}` : "Welcome to JAAJ");
      navigate("/account?tab=security", { replace: true });
    } catch (error) {
      if (error instanceof ApiError) {
        setErrors({
          name: error.errors?.name?.[0] ?? "",
          email: error.errors?.email?.[0] ?? "",
          phone: error.errors?.phone?.[0] ?? "",
          password: error.errors?.password?.[0] ?? error.message,
        });
        return;
      }
      setErrors({ email: "Registration failed. Please try again." });
    }
  };

  const set = (key: keyof typeof form, value: string | boolean) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => ({ ...prev, [key]: "" }));
  };

  return (
    <div className="max-w-md">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Join JAAJ</p>
      <h1 className="mt-3 font-display text-4xl text-ink dark:text-linen">Create your account</h1>
      <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
        One account for orders, wishlist, early access and the Sunday letter.
      </p>

      <form onSubmit={submit} noValidate className="mt-8 space-y-5">
        <Field label="Full name" htmlFor="reg-name" required error={errors.name}>
          <Input id="reg-name" autoComplete="name" value={form.name} onChange={(e) => set("name", e.target.value)} aria-invalid={Boolean(errors.name)} />
        </Field>
        <Field label="Email" htmlFor="reg-email" required error={errors.email}>
          <Input id="reg-email" type="email" autoComplete="email" value={form.email} onChange={(e) => set("email", e.target.value)} aria-invalid={Boolean(errors.email)} />
        </Field>
        <Field label="Phone" htmlFor="reg-phone" error={errors.phone}>
          <Input id="reg-phone" type="tel" autoComplete="tel" value={form.phone} onChange={(e) => set("phone", e.target.value)} aria-invalid={Boolean(errors.phone)} />
        </Field>
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Password" htmlFor="reg-password" required error={errors.password} hint="8+ characters">
            <Input id="reg-password" type="password" autoComplete="new-password" value={form.password} onChange={(e) => set("password", e.target.value)} aria-invalid={Boolean(errors.password)} />
          </Field>
          <Field label="Confirm password" htmlFor="reg-confirm" required error={errors.confirm}>
            <Input id="reg-confirm" type="password" autoComplete="new-password" value={form.confirm} onChange={(e) => set("confirm", e.target.value)} aria-invalid={Boolean(errors.confirm)} />
          </Field>
        </div>
        <div>
          <label className="flex cursor-pointer items-start gap-2.5 text-sm text-smoke dark:text-linen-dim">
            <input
              type="checkbox"
              checked={form.terms}
              onChange={(e) => set("terms", e.target.checked)}
              className="mt-0.5 h-4 w-4 accent-bronze"
              aria-invalid={Boolean(errors.terms)}
            />
            <span>
              I agree to the{" "}
              <Link to="/terms" className="font-semibold text-bronze underline underline-offset-2">Terms</Link> and{" "}
              <Link to="/privacy" className="font-semibold text-bronze underline underline-offset-2">Privacy Policy</Link>.
            </span>
          </label>
          {errors.terms && (
            <p role="alert" className="mt-1.5 text-xs font-semibold text-red-700 dark:text-red-400">{errors.terms}</p>
          )}
        </div>
        <Button type="submit" size="lg" className="w-full" icon={UserPlus} loading={status === "loading"}>
          {status === "loading" ? "Creating Account…" : "Create Account"}
        </Button>
      </form>

      <p className="mt-6 border-t border-line pt-6 text-sm text-smoke dark:border-line-dark dark:text-linen-dim">
        Already a member?{" "}
        <Link to="/login" className="font-bold text-ink underline-offset-4 hover:text-bronze hover:underline dark:text-linen">
          Sign in
        </Link>
      </p>
    </div>
  );
}
