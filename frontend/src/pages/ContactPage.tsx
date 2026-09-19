import { useState, type FormEvent } from "react";
import { Check, Mail, MapPin, Phone } from "lucide-react";
import { Button, Field, Input, Select, Textarea } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

export default function ContactPage() {
  usePageTitle("Contact", "Reach the JAAJ studio — questions on orders, sizing, press or wholesale.");
  const [form, setForm] = useState({ name: "", email: "", topic: "Order support", message: "" });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const update = (key: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    setForm((prev) => ({ ...prev, [key]: e.target.value }));
    setErrors((prev) => ({ ...prev, [key]: "" }));
  };

  const submit = (event: FormEvent) => {
    event.preventDefault();
    const next: Record<string, string> = {};
    if (!form.name.trim()) next.name = "Your name is required.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(form.email)) next.email = "Enter a valid email address.";
    if (form.message.trim().length < 10) next.message = "Tell us a little more (10+ characters).";
    setErrors(next);
    if (Object.keys(next).length) return;
    setSending(true);
    window.setTimeout(() => {
      setSending(false);
      setSent(true);
    }, 900);
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-14 sm:px-6 lg:py-20">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Contact</p>
      <h1 className="mt-3 font-display text-4xl leading-tight text-ink sm:text-5xl dark:text-linen">
        Talk to jaaj
      </h1>
      <p className="mt-4 max-w-xl text-sm leading-relaxed text-smoke sm:text-base dark:text-linen-dim">
        Sizing advice, order questions, press or wholesale — a human replies within one business day.
      </p>

      <div className="mt-12 grid gap-12 lg:grid-cols-[1fr_360px]">
        {sent ? (
          <div className="flex flex-col items-start gap-4 border border-line bg-cream p-8 dark:border-line-dark dark:bg-nox2" role="status">
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-ink text-paper dark:bg-linen dark:text-nox">
              <Check className="h-6 w-6" aria-hidden />
            </span>
            <h2 className="font-display text-2xl text-ink dark:text-linen">Message received.</h2>
            <p className="text-sm leading-relaxed text-smoke dark:text-linen-dim">
              Thank you, {form.name.split(" ")[0] || "friend"}. We've sent a copy to {form.email} and
              will reply within one business day.
            </p>
            <Button variant="outline" onClick={() => { setSent(false); setForm({ name: "", email: "", topic: "Order support", message: "" }); }}>
              Send another message
            </Button>
          </div>
        ) : (
          <form onSubmit={submit} noValidate className="space-y-5">
            <div className="grid gap-5 sm:grid-cols-2">
              <Field label="Name" htmlFor="contact-name" required error={errors.name}>
                <Input id="contact-name" autoComplete="name" value={form.name} onChange={update("name")} aria-invalid={Boolean(errors.name)} />
              </Field>
              <Field label="Email" htmlFor="contact-email" required error={errors.email}>
                <Input id="contact-email" type="email" autoComplete="email" value={form.email} onChange={update("email")} aria-invalid={Boolean(errors.email)} />
              </Field>
            </div>
            <Field label="Topic" htmlFor="contact-topic">
              <Select id="contact-topic" value={form.topic} onChange={update("topic")}>
                <option>Order support</option>
                <option>Sizing advice</option>
                <option>Returns &amp; exchanges</option>
                <option>Press</option>
                <option>Wholesale</option>
              </Select>
            </Field>
            <Field label="Message" htmlFor="contact-message" required error={errors.message}>
              <Textarea id="contact-message" value={form.message} onChange={update("message")} aria-invalid={Boolean(errors.message)} placeholder="How can we help?" />
            </Field>
            <Button type="submit" size="lg" loading={sending}>
              {sending ? "Sending…" : "Send Message"}
            </Button>
          </form>
        )}

        <aside className="space-y-6" aria-label="Contact details">
          {[
            { icon: Mail, title: "Email", lines: ["care@jaaj.studio", "Replies within 24h, Mon–Fri"] },
            { icon: Phone, title: "Phone", lines: ["+45 33 12 40 90", "Mon–Fri, 09:00–17:00 CET"] },
            { icon: MapPin, title: "Flagship jaaj studio", lines: ["Jægersborggade 22", "2200 Copenhagen N, Denmark"] },
          ].map(({ icon: Icon, title, lines }) => (
            <div key={title} className="flex gap-4 border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2">
              <Icon className="mt-0.5 h-5 w-5 shrink-0 text-bronze" aria-hidden />
              <div>
                <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">{title}</h2>
                {lines.map((line, i) => (
                  <p key={line} className={i === 0 ? "mt-1.5 text-sm font-semibold text-ink dark:text-linen" : "text-xs text-smoke dark:text-linen-dim"}>
                    {line}
                  </p>
                ))}
              </div>
            </div>
          ))}
        </aside>
      </div>
    </div>
  );
}
