import { useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { ArrowLeft, MailCheck, Send } from "lucide-react";
import { Button, Field, Input } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { api } from "@/lib/api";

export default function ForgotPasswordPage() {
  usePageTitle("Reset Password");
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      setError("Enter a valid email address.");
      return;
    }
    setError("");
    setSending(true);
    try {
      await api("/auth/forgot-password", {
        method: "POST",
        body: JSON.stringify({ email }),
      });
    } finally {
      setSending(false);
      setSent(true);
    }
  };

  return (
    <div className="max-w-md">
      {sent ? (
        <div role="status">
          <span className="flex h-14 w-14 items-center justify-center rounded-full bg-ink text-paper dark:bg-linen dark:text-nox">
            <MailCheck className="h-7 w-7" aria-hidden />
          </span>
          <h1 className="mt-6 font-display text-4xl text-ink dark:text-linen">Check your inbox</h1>
          <p className="mt-3 text-sm leading-relaxed text-smoke dark:text-linen-dim">
            If an account exists for <strong className="text-ink dark:text-linen">{email}</strong>, a
            reset link is on its way. It expires in 30 minutes.
          </p>
          <Link
            to="/login"
            className="mt-6 inline-flex items-center gap-2 text-xs font-bold tracking-[0.16em] uppercase text-bronze underline-offset-4 hover:underline"
          >
            <ArrowLeft className="h-4 w-4" aria-hidden /> Back to sign in
          </Link>
        </div>
      ) : (
        <>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Account recovery</p>
          <h1 className="mt-3 font-display text-4xl text-ink dark:text-linen">Reset your password</h1>
          <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
            Enter the email you registered with and we'll send a secure reset link.
          </p>
          <form onSubmit={submit} noValidate className="mt-8 space-y-5">
            <Field label="Email" htmlFor="forgot-email" required error={error}>
              <Input
                id="forgot-email"
                type="email"
                autoComplete="email"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setError(""); }}
                aria-invalid={Boolean(error)}
              />
            </Field>
            <Button type="submit" size="lg" className="w-full" icon={Send} loading={sending}>
              {sending ? "Sending…" : "Send Reset Link"}
            </Button>
          </form>
          <Link
            to="/login"
            className="mt-6 inline-flex items-center gap-2 text-xs font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 hover:text-ink hover:underline dark:text-linen-dim dark:hover:text-linen"
          >
            <ArrowLeft className="h-4 w-4" aria-hidden /> Back to sign in
          </Link>
        </>
      )}
    </div>
  );
}
