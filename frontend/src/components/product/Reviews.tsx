import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { BadgeCheck, Pencil, Star, Trash2 } from "lucide-react";
import { ApiError, api, hasApiToken } from "@/lib/api";
import { Button, EmptyState, Field, Input, SectionHeading, Spinner } from "@/components/ui/primitives";
import { Rating } from "@/components/product/Rating";
import { cn } from "@/utils/cn";

export interface ReviewItem {
  id: number;
  rating: number;
  title: string | null;
  body: string;
  status: string;
  verifiedPurchase: boolean;
  customerName: string | null;
  createdAt: string | null;
}

interface ReviewSummary {
  count: number;
  average: number;
  distribution: Record<string, number>;
}

interface ReviewCollection {
  data: ReviewItem[];
  meta?: { current_page: number; last_page: number; total: number };
}

interface Eligibility {
  eligible: boolean;
  reason: string | null;
  hasReviewed: boolean;
  verifiedPurchase: boolean;
}

function StarInput({ value, onChange, disabled }: { value: number; onChange: (stars: number) => void; disabled?: boolean }) {
  const [hovered, setHovered] = useState(0);
  const shown = hovered || value;
  return (
    <div className="flex gap-1" role="radiogroup" aria-label="Your rating">
      {[1, 2, 3, 4, 5].map((stars) => (
        <button
          key={stars}
          type="button"
          role="radio"
          aria-checked={value === stars}
          aria-label={`${stars} star${stars > 1 ? "s" : ""}`}
          disabled={disabled}
          onClick={() => onChange(stars)}
          onMouseEnter={() => setHovered(stars)}
          onMouseLeave={() => setHovered(0)}
          className="p-0.5 transition-transform hover:scale-110 disabled:cursor-not-allowed disabled:hover:scale-100"
        >
          <Star
            aria-hidden
            className={cn(
              "h-7 w-7",
              stars <= shown ? "fill-bronze text-bronze" : "text-line dark:text-line-dark",
            )}
          />
        </button>
      ))}
    </div>
  );
}

function formatDate(value: string | null) {
  if (!value) return "";
  return new Date(value).toLocaleDateString("en-US", { day: "numeric", month: "short", year: "numeric" });
}

export function Reviews({ slug, productName }: { slug: string; productName: string }) {
  const authed = hasApiToken();
  const [summary, setSummary] = useState<ReviewSummary | null>(null);
  const [reviews, setReviews] = useState<ReviewItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [mine, setMine] = useState<ReviewItem | null>(null);
  const [eligibility, setEligibility] = useState<Eligibility | null>(null);

  const [rating, setRating] = useState(5);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [formError, setFormError] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  const loadPage = useCallback(
    async (nextPage: number, append: boolean) => {
      const response = await api<ReviewCollection>(
        `/products/${encodeURIComponent(slug)}/reviews?per_page=5&page=${nextPage}`,
      );
      setReviews((current) => (append ? [...current, ...response.data] : response.data));
      setLastPage(response.meta?.last_page ?? 1);
      setPage(nextPage);
    },
    [slug],
  );

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setReviews([]);
    setMine(null);
    setEligibility(null);
    setEditing(false);
    (async () => {
      try {
        const [summaryResponse] = await Promise.all([
          api<{ data: ReviewSummary }>(`/products/${encodeURIComponent(slug)}/reviews/summary`),
        ]);
        if (cancelled) return;
        setSummary(summaryResponse.data);
        await loadPage(1, false);
        if (cancelled) return;
        if (hasApiToken()) {
          const [mineResponse, eligibilityResponse] = await Promise.all([
            api<{ data: ReviewItem }>(`/products/${encodeURIComponent(slug)}/reviews/mine`).catch(() => null),
            api<{ data: Eligibility }>(`/products/${encodeURIComponent(slug)}/reviews/eligibility`).catch(() => null),
          ]);
          if (cancelled) return;
          if (mineResponse) {
            setMine(mineResponse.data);
            setRating(mineResponse.data.rating);
            setTitle(mineResponse.data.title ?? "");
            setBody(mineResponse.data.body);
          }
          if (eligibilityResponse) setEligibility(eligibilityResponse.data);
        }
      } catch {
        if (!cancelled) setSummary(null);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [slug, loadPage]);

  const showMore = async () => {
    setLoadingMore(true);
    try {
      await loadPage(page + 1, true);
    } finally {
      setLoadingMore(false);
    }
  };

  const submit = async () => {
    setSaving(true);
    setFormError("");
    setFieldErrors({});
    try {
      if (editing && mine) {
        const response = await api<{ data: ReviewItem }>(`/reviews/${mine.id}`, {
          method: "PUT",
          body: JSON.stringify({ rating, title: title || null, body }),
        });
        setMine(response.data);
        setEditing(false);
      } else {
        const response = await api<{ data: ReviewItem }>(`/products/${encodeURIComponent(slug)}/reviews`, {
          method: "POST",
          body: JSON.stringify({ rating, title: title || null, body }),
        });
        setMine(response.data);
        setEligibility({ eligible: false, reason: "You have already reviewed this product.", hasReviewed: true, verifiedPurchase: true });
      }
    } catch (error) {
      if (error instanceof ApiError) {
        setFormError(error.message);
        if (error.errors) setFieldErrors(error.errors);
      } else {
        setFormError(error instanceof Error ? error.message : "Could not save your review.");
      }
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!mine || !window.confirm(`Delete your review of ${productName}?`)) return;
    setDeleting(true);
    try {
      await api(`/reviews/${mine.id}`, { method: "DELETE" });
      setMine(null);
      setEditing(false);
      setRating(5);
      setTitle("");
      setBody("");
      const eligibilityResponse = await api<{ data: Eligibility }>(
        `/products/${encodeURIComponent(slug)}/reviews/eligibility`,
      ).catch(() => null);
      if (eligibilityResponse) setEligibility(eligibilityResponse.data);
    } catch (error) {
      setFormError(error instanceof Error ? error.message : "Could not delete your review.");
    } finally {
      setDeleting(false);
    }
  };

  const canWrite = authed && eligibility?.eligible === true && !editing;
  const showForm = canWrite || editing;

  return (
    <section aria-labelledby="reviews-heading" id="reviews" className="mx-auto max-w-[1440px] scroll-mt-24 px-4 pb-16 sm:px-6 lg:px-10 lg:pb-24">
      <SectionHeading kicker="Customer Feedback" title="Reviews" />

      {loading ? (
        <div className="mt-8 flex justify-center py-10" role="status" aria-label="Loading reviews">
          <Spinner />
        </div>
      ) : (
        <div className="mt-8 grid gap-10 lg:grid-cols-[320px_1fr] lg:gap-14">
          {/* Summary */}
          <div>
            <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
              <p className="font-display text-5xl text-ink dark:text-linen">
                {(summary?.average ?? 0).toFixed(1)}
              </p>
              <div className="mt-2">
                <Rating value={summary?.average ?? 0} showCount={false} />
              </div>
              <p className="mt-2 text-xs font-semibold tracking-[0.14em] uppercase text-smoke dark:text-linen-dim">
                {summary?.count ?? 0} verified review{(summary?.count ?? 0) === 1 ? "" : "s"}
              </p>
              <dl className="mt-5 space-y-2">
                {[5, 4, 3, 2, 1].map((stars) => {
                  const total = summary?.distribution?.[String(stars)] ?? 0;
                  const count = summary?.count ?? 0;
                  const width = count > 0 ? Math.round((total / count) * 100) : 0;
                  return (
                    <div key={stars} className="flex items-center gap-2 text-xs">
                      <dt className="w-7 shrink-0 font-bold text-ink dark:text-linen">{stars} ★</dt>
                      <dd className="h-1.5 flex-1 bg-fog dark:bg-nox3" aria-hidden>
                        <div className="h-full bg-bronze" style={{ width: `${width}%` }} />
                      </dd>
                      <dd className="w-8 shrink-0 text-right text-smoke dark:text-linen-dim">{total}</dd>
                    </div>
                  );
                })}
              </dl>
            </div>
          </div>

          {/* List + form */}
          <div>
            {!authed && (
              <p className="border border-line bg-cream px-5 py-4 text-sm text-smoke dark:border-line-dark dark:bg-nox2 dark:text-linen-dim">
                <Link to="/login" className="font-bold text-ink underline-offset-4 hover:text-bronze hover:underline dark:text-linen">
                  Sign in
                </Link>{" "}
                to write a review. Only customers with a delivered order can review this piece.
              </p>
            )}

            {authed && eligibility && !eligibility.eligible && !eligibility.hasReviewed && (
              <p className="border border-line bg-cream px-5 py-4 text-sm text-smoke dark:border-line-dark dark:bg-nox2 dark:text-linen-dim" role="note">
                {eligibility.reason}
              </p>
            )}

            {mine && !editing && (
              <article className="border border-bronze/40 bg-cream px-5 py-4 dark:border-bronze/40 dark:bg-nox2">
                <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-bronze">Your review</p>
                <div className="mt-2">
                  <Rating value={mine.rating} showCount={false} />
                </div>
                {mine.title && <h3 className="mt-2 font-semibold text-ink dark:text-linen">{mine.title}</h3>}
                <p className="mt-1 text-sm leading-relaxed text-smoke dark:text-linen-dim">{mine.body}</p>
                {mine.status === "pending" && (
                  <p className="mt-2 text-xs font-semibold text-bronze" role="note">
                    Awaiting moderation — it will appear below once approved.
                  </p>
                )}
                <div className="mt-3 flex gap-2">
                  <Button type="button" size="sm" variant="outline" icon={Pencil} onClick={() => { setEditing(true); setFormError(""); setFieldErrors({}); }}>
                    Edit
                  </Button>
                  <Button type="button" size="sm" variant="ghost" icon={Trash2} loading={deleting} onClick={() => void remove()}>
                    Delete
                  </Button>
                </div>
              </article>
            )}

            {showForm && (
              <form
                className="mt-6 border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2 sm:p-6"
                onSubmit={(event) => {
                  event.preventDefault();
                  void submit();
                }}
              >
                <h3 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
                  {editing ? "Edit your review" : "Write a review"}
                </h3>
                <div className="mt-4">
                  <StarInput value={rating} onChange={setRating} disabled={saving} />
                  {fieldErrors.rating && <p className="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{fieldErrors.rating[0]}</p>}
                </div>
                <div className="mt-4">
                  <Field label="Headline" htmlFor="review-title" error={fieldErrors.title?.[0]}>
                    <Input
                      id="review-title"
                      value={title}
                      maxLength={120}
                      disabled={saving}
                      onChange={(event) => setTitle(event.target.value)}
                      placeholder="Sum it up in a line (optional)"
                    />
                  </Field>
                </div>
                <div className="mt-4">
                  <Field label="Review" htmlFor="review-body" required error={fieldErrors.body?.[0]}>
                    <textarea
                      id="review-body"
                      value={body}
                      maxLength={2000}
                      rows={4}
                      disabled={saving}
                      onChange={(event) => setBody(event.target.value)}
                      placeholder="How is the fit, fabric and finish?"
                      className="w-full border border-line bg-paper px-3 py-2.5 text-sm text-ink placeholder:text-smoke/60 focus:border-bronze focus:outline-none dark:border-line-dark dark:bg-nox dark:text-linen"
                    />
                  </Field>
                </div>
                {formError && (
                  <p className="mt-3 text-sm text-red-700 dark:text-red-400" role="alert">{formError}</p>
                )}
                <div className="mt-4 flex gap-2">
                  <Button type="submit" loading={saving}>
                    {editing ? "Save changes" : "Submit review"}
                  </Button>
                  {editing && (
                    <Button
                      type="button"
                      variant="ghost"
                      disabled={saving}
                      onClick={() => {
                        setEditing(false);
                        setFormError("");
                        setFieldErrors({});
                        if (mine) {
                          setRating(mine.rating);
                          setTitle(mine.title ?? "");
                          setBody(mine.body);
                        }
                      }}
                    >
                      Cancel
                    </Button>
                  )}
                </div>
                {!editing && (
                  <p className="mt-3 text-xs text-smoke dark:text-linen-dim">
                    Reviews are moderated before they appear publicly.
                  </p>
                )}
              </form>
            )}

            {/* Approved list */}
            <div className="mt-8">
              {reviews.length === 0 ? (
                <EmptyState
                  icon={Star}
                  title="No reviews yet"
                  body={mine ? "Yours is on its way — see above." : "Be the first to share how this piece wears."}
                />
              ) : (
                <ul className="divide-y divide-line dark:divide-line-dark">
                  {reviews.map((review) => (
                    <li key={review.id} className="py-5 first:pt-0">
                      <div className="flex items-center justify-between gap-3">
                        <Rating value={review.rating} showCount={false} />
                        <time className="text-xs text-smoke dark:text-linen-dim">{formatDate(review.createdAt)}</time>
                      </div>
                      {review.title && <h3 className="mt-2 font-semibold text-ink dark:text-linen">{review.title}</h3>}
                      <p className="mt-1 text-sm leading-relaxed text-smoke dark:text-linen-dim">{review.body}</p>
                      <p className="mt-2 flex items-center gap-2 text-xs text-smoke dark:text-linen-dim">
                        <span className="font-semibold text-ink dark:text-linen">{review.customerName ?? "Verified customer"}</span>
                        {review.verifiedPurchase && (
                          <span className="inline-flex items-center gap-1 font-semibold text-bronze">
                            <BadgeCheck className="h-3.5 w-3.5" aria-hidden /> Verified purchase
                          </span>
                        )}
                      </p>
                    </li>
                  ))}
                </ul>
              )}
              {page < lastPage && (
                <div className="mt-6 flex justify-center">
                  <Button type="button" variant="outline" loading={loadingMore} onClick={() => void showMore()}>
                    Show more reviews
                  </Button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
