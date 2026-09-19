import { Component, type ReactNode } from "react";
import { Link } from "react-router-dom";
import { TriangleAlert } from "lucide-react";

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false };

  static getDerivedStateFromError(): State {
    return { hasError: true };
  }

  componentDidCatch(error: unknown) {
    console.error("JAAJ runtime error:", error);
  }

  render() {
    if (!this.state.hasError) return this.props.children;
    return (
      <main className="flex min-h-[70vh] flex-col items-center justify-center gap-5 bg-paper px-6 text-center dark:bg-nox">
        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-fog dark:bg-nox2">
          <TriangleAlert className="h-7 w-7 text-bronze" aria-hidden />
        </span>
        <h1 className="font-display text-3xl text-ink dark:text-linen">Something went wrong</h1>
        <p className="max-w-md text-sm leading-relaxed text-smoke dark:text-linen-dim">
          An unexpected error occurred while rendering this page. You can reload, or return to the
          homepage and continue browsing.
        </p>
        <div className="flex flex-wrap items-center justify-center gap-3">
          <button
            type="button"
            onClick={() => window.location.reload()}
            className="border border-ink bg-ink px-6 py-3 text-xs font-bold tracking-[0.16em] uppercase text-paper transition-colors hover:bg-bronze-deep dark:border-linen dark:bg-linen dark:text-nox"
          >
            Reload page
          </button>
          <Link
            to="/"
            className="border border-ink/25 px-6 py-3 text-xs font-bold tracking-[0.16em] uppercase text-ink transition-colors hover:border-ink dark:border-linen/30 dark:text-linen"
          >
            Back to home
          </Link>
        </div>
      </main>
    );
  }
}
