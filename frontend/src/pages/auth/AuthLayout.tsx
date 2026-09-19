import { Link, Outlet } from "react-router-dom";
import logoTextImg from "@/assets/imagery/TEXT-01-01.png";

export default function AuthLayout() {
  return (
    <div className="grid min-h-[calc(100vh-104px)] lg:grid-cols-2">
      <div className="flex flex-col px-4 py-12 sm:px-10 lg:px-20">
        {/* অরিজিনাল লেআউট পজিশনে ইমেজ লোগো (উইডথ বাড়ানো ও হাইট অটো) */}
        <Link to="/" aria-label="JAAJ — home" className="inline-block">
          <img
            src={logoTextImg}
            alt="JAAJ Logo"
            className="w-56 h-auto object-contain dark:invert sm:w-64 lg:w-72"
          />
        </Link>

        <div className="flex flex-1 flex-col justify-center py-12">
          <Outlet />
        </div>
      </div>
      <div className="relative hidden overflow-hidden bg-ink lg:block" aria-hidden>
        <img
          src="https://images.pexels.com/photos/7318683/pexels-photo-7318683.jpeg?auto=compress&cs=tinysrgb&fit=crop&w=1000&h=1400"
          alt=""
          width={800}
          height={1150}
          loading="lazy"
          decoding="async"
          className="absolute inset-0 h-full w-full object-cover opacity-90"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink/80 via-transparent to-transparent" />
        <blockquote className="absolute inset-x-0 bottom-0 p-12">
          <p className="font-display text-3xl leading-snug text-cream">
            “Buy once, buy well — the quietest wardrobe is the loudest statement.”
          </p>
          <footer className="mt-3 text-xs font-bold tracking-[0.2em] uppercase text-paper/70">
            The JAAJ Journal
          </footer>
        </blockquote>
      </div>
    </div>
  );
}