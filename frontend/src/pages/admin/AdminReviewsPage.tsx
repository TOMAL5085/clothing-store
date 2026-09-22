import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { BadgeCheck, CheckCircle, ShieldAlert, Star, XCircle } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Field, Select, Spinner } from "@/components/ui/primitives";
import { Rating } from "@/components/product/Rating";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

interface AdminReview {
  id: number;
  rating: number;
  title: string | null;
  body: string;
  status: string;
  verifiedPurchase: boolean;
  customerName: string | null;
  productId: string | null;
  productSlug: string | null;
  productName: string | null;
  createdAt: string | null;
}

interface ReviewCollection {
  data: AdminReview[];
  meta?: PaginationMeta;
}

const STATUSES = ["pending", "approved", "rejected"];

function statusTone(status: string) {
  if (status === "approved") return "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300";
  if (status === "rejected") return "bg-red-800/10 text-red-800 dark:text-red-300";
  return "bg-bronze/15 text-bronze";
}

export default function AdminReviewsPage() {
  usePageTitle("Reviews");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [reviews, setReviews] = useState<AdminReview[]>([]);
  const [status, setStatus] = useState("pending");
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<number | undefined>();
  const [error, setError] = useState("");

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "50" });
    return params.toString();
  }, [status]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    setError("");
    api<ReviewCollection>(`/admin/reviews?${searchParams}`)
      .then((response) => {
        if (!cancelled) setReviews(response.data);
      })
      .catch((requestError) => {
        if (!cancelled) setError(requestError instanceof Error ? requestError.message : "Reviews could not be loaded.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [searchParams, user?.role]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
        <EmptyState
          icon={ShieldAlert}
          title="Admins only"
          body="Your account does not have permission to moderate reviews."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const moderate = async (id: number, decision: "approved" | "rejected") => {
    setSavingId(id);
    try {
      const response = await api<{ data: AdminReview }>(`/admin/reviews/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ decision }),
      });
      setReviews((current) =>
        status === "all"
          ? current.map((review) => (review.id === id ? response.data : review))
          : current.filter((review) => review.id !== id),
      );
      pushToast(`Review ${decision}`);
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Moderation failed");
    } finally {
      setSavingId(undefined);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Reviews</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/admin/analytics" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Analytics</Link>
          <Link to="/admin/audit-log" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Audit Log</Link>
          <Link to="/admin/content" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Content</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[220px_1fr]">
        <Field label="Moderation status" htmlFor="admin-review-status">
          <Select id="admin-review-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {STATUSES.map((s) => (
              <option key={s} value={s} className="capitalize">{s}</option>
            ))}
          </Select>
        </Field>
        <p className="self-end pb-2 text-xs text-smoke dark:text-linen-dim">
          Only approved reviews appear on product pages and affect ratings.
        </p>
      </div>

      {loading ? (
        <div className="flex justify-center py-16" role="status" aria-label="Loading reviews">
          <Spinner />
        </div>
      ) : error ? (
        <p className="mt-8 text-sm text-red-700 dark:text-red-400" role="alert">{error}</p>
      ) : reviews.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={Star}
            title="Nothing to moderate"
            body={status === "all" ? "No reviews have been submitted yet." : `No ${status} reviews right now.`}
          />
        </div>
      ) : (
        <ul className="mt-8 space-y-4">
          {reviews.map((review) => (
            <li key={review.id} className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <Rating value={review.rating} showCount={false} />
                  <span className={cn("px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase", statusTone(review.status))}>
                    {review.status}
                  </span>
                  {review.verifiedPurchase && (
                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-bronze">
                      <BadgeCheck className="h-3.5 w-3.5" aria-hidden /> Verified purchase
                    </span>
                  )}
                </div>
                <div className="flex gap-2">
                  {review.status !== "approved" && (
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      icon={CheckCircle}
                      loading={savingId === review.id}
                      onClick={() => void moderate(review.id, "approved")}
                    >
                      Approve
                    </Button>
                  )}
                  {review.status !== "rejected" && (
                    <Button
                      type="button"
                      size="sm"
                      variant="ghost"
                      icon={XCircle}
                      loading={savingId === review.id}
                      onClick={() => void moderate(review.id, "rejected")}
                    >
                      Reject
                    </Button>
                  )}
                </div>
              </div>
              {review.title && <h2 className="mt-3 font-semibold text-ink dark:text-linen">{review.title}</h2>}
              <p className="mt-1 text-sm leading-relaxed text-smoke dark:text-linen-dim">{review.body}</p>
              <p className="mt-3 text-xs text-smoke dark:text-linen-dim">
                <span className="font-semibold text-ink dark:text-linen">{review.customerName ?? "Customer"}</span>
                {" on "}
                {review.productSlug ? (
                  <Link to={`/product/${review.productSlug}`} className="font-semibold underline-offset-4 hover:text-bronze hover:underline">
                    {review.productName ?? review.productId}
                  </Link>
                ) : (
                  <span>{review.productName ?? review.productId}</span>
                )}
                {review.createdAt && <> · {new Date(review.createdAt).toLocaleDateString("en-US", { day: "numeric", month: "short", year: "numeric" })}</>}
              </p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
