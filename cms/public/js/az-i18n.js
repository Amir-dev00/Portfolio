/* Shared EN/FA locale dictionary + live language switching. */
(function () {
  "use strict";

  var STORAGE_KEY = "az-lang";
  var listeners = [];

  var STRINGS = {
    en: {
      "meta.description":
        "Portfolio of Amirhossein Zamanzade — Full-Stack Developer, Programmer, and Designer.",
      "title.base": "Amirhossein Zamanzade | Developer",
      "title.work": "Work — Amirhossein Zamanzade | Developer",
      "title.about": "About — Amirhossein Zamanzade | Developer",
      "title.contact": "Contact — Amirhossein Zamanzade | Developer",
      "title.project": "{name} — Amirhossein Zamanzade | Developer",
      "nav.primary": "Primary",
      "nav.work": "Work",
      "nav.about": "About",
      "nav.home": "Home",
      "nav.cv": "CV",
      "nav.contact": "Contact",
      "nav.tooltip.work": "Work",
      "nav.tooltip.about": "About",
      "nav.tooltip.home": "Home",
      "nav.tooltip.cv": "Download CV",
      "nav.tooltip.contact": "Contact",
      "nav.aria.cv": "Download CV",
      "lang.switcher": "Language",
      "lang.en": "English",
      "lang.fa": "فارسی",
      "lang.aria.en": "Read this site in English",
      "lang.aria.fa": "نمایش سایت به فارسی",
      "home.profile.aria": "Amirhossein Zamanzade, Software Engineer",
      "home.profile.name": "Amirhossein Zamanzade",
      "home.profile.role": "Software Engineer",
      "home.arrows": "Project slides",
      "home.prev": "Previous project",
      "home.next": "Next project",
      "home.slide.personal-portfolio-website": "Personal Portfolio Website",
      "home.slide.shopping-mall-desktop": "Shopping Mall Desktop",
      "home.slide.rest-api-microservice": "REST API Microservice",
      "home.slide.telegram-automation-bot": "Telegram Automation Bot",
      "work.aria": "Work",
      "work.eyebrow": "Selected work",
      "work.title": "Selected projects.",
      "work.lead": "A selection of websites, applications, APIs, and automation tools.",
      "work.spotlight.eyebrow": "Selected work",
      "work.explore": "Explore project",
      "work.open": "Open {name}",
      "work.count": "{n} projects",
      "work.prev": "Previous project",
      "work.next": "Next project",
      "work.listbox": "Select a project; use up and down arrow keys",
      "project.aria": "Project",
      "project.back": "← Back to Work",
      "project.missing.title": "Project not found",
      "project.missing.lead": "That project is unavailable. Return to the work grid to continue.",
      "project.role": "Role:",
      "project.github": "GitHub",
      "project.live": "Live",
      "about.aria": "About",
      "about.eyebrow": "About",
      "about.title": "Software engineer.",
      "about.portrait.alt": "Portrait of Amirhossein Zamanzade",
      "about.meta.location.label": "Location:",
      "about.meta.location.value": "Tehran, Iran",
      "about.meta.education.label": "Education:",
      "about.meta.education.value": "B.Sc. Computer Science, Amirkabir University of Technology",
      "about.meta.status.label": "Status:",
      "about.meta.status.value": "Available for freelance",
      "about.p1":
        "I’m Amirhossein Zamanzade, a Computer Science student and Full-Stack Developer focused on fast, scalable, and user-centric products. I care about clean architecture, modern tooling, and interfaces that feel refined.",
      "about.p2":
        "This portfolio reflects my learning journey across web, desktop, APIs, and automation — and a place to connect for collaboration.",
      "about.skills": "Skills",
      "contact.aria": "Contact",
      "contact.eyebrow": "Let’s connect",
      "contact.title": "Let’s talk.",
      "contact.intro": "Have a project or a role in mind? Let’s talk.",
      "contact.emailMe": "Email me",
      "contact.downloadCv": "Download CV",
      "contact.linkedin": "LinkedIn",
      "contact.linkedin.sub": "Start a conversation",
      "contact.github": "GitHub",
      "contact.github.sub": "Code & repositories",
      "contact.telegram": "Telegram",
      "contact.telegram.sub": "Quick chat · @Amir_dev00",
      "contact.note": "Available for freelance collaborations.",
      "category.Websites": "Websites",
      "category.Applications": "Applications",
      "category.APIs & Backend": "APIs & Backend",
      "category.Automation / Bots": "Automation / Bots",
    },
    fa: {
      "meta.description":
        "نمونه‌کارهای امیرحسین زمان‌زاده — توسعه‌دهنده فول‌استک، برنامه‌نویس و طراح.",
      "title.base": "امیرحسین زمان‌زاده | توسعه‌دهنده",
      "title.work": "کارها — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "title.about": "درباره — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "title.contact": "تماس — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "title.project": "{name} — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "nav.primary": "ناوبری اصلی",
      "nav.work": "کارها",
      "nav.about": "درباره",
      "nav.home": "خانه",
      "nav.cv": "رزومه",
      "nav.contact": "تماس",
      "nav.tooltip.work": "کارها",
      "nav.tooltip.about": "درباره",
      "nav.tooltip.home": "خانه",
      "nav.tooltip.cv": "دانلود رزومه",
      "nav.tooltip.contact": "تماس",
      "nav.aria.cv": "دانلود رزومه",
      "lang.switcher": "زبان",
      "lang.en": "English",
      "lang.fa": "فارسی",
      "lang.aria.en": "Read this site in English",
      "lang.aria.fa": "نمایش سایت به فارسی",
      "home.profile.aria": "امیرحسین زمان‌زاده، مهندس نرم‌افزار",
      "home.profile.name": "امیرحسین زمان زاده",
      "home.profile.role": "مهندس نرم‌افزار",
      "home.arrows": "اسلاید پروژه‌ها",
      "home.prev": "پروژه قبلی",
      "home.next": "پروژه بعدی",
      "home.slide.personal-portfolio-website": "وب‌سایت نمونه‌کار شخصی",
      "home.slide.shopping-mall-desktop": "اپلیکیشن دسکتاپ مرکز خرید",
      "home.slide.rest-api-microservice": "میکروسرویس REST API",
      "home.slide.telegram-automation-bot": "ربات اتوماسیون تلگرام",
      "work.aria": "کارها",
      "work.eyebrow": "کارهای منتخب",
      "work.title": "پروژه‌های منتخب.",
      "work.lead": "گزیده‌ای از وب‌سایت‌ها، اپلیکیشن‌ها، APIها و ابزارهای اتوماسیون.",
      "work.spotlight.eyebrow": "کار منتخب",
      "work.explore": "مشاهده پروژه",
      "work.open": "باز کردن {name}",
      "work.count": "{n} پروژه",
      "work.prev": "پروژه قبلی",
      "work.next": "پروژه بعدی",
      "work.listbox": "انتخاب پروژه؛ با کلیدهای بالا و پایین جابه‌جا شوید",
      "project.aria": "پروژه",
      "project.back": "→ بازگشت به کارها",
      "project.missing.title": "پروژه پیدا نشد",
      "project.missing.lead": "این پروژه در دسترس نیست. به فهرست کارها برگردید.",
      "project.role": "نقش:",
      "project.github": "گیت‌هاب",
      "project.live": "نسخه آنلاین",
      "about.aria": "درباره",
      "about.eyebrow": "درباره",
      "about.title": "مهندس نرم‌افزار.",
      "about.portrait.alt": "پرتره امیرحسین زمان‌زاده",
      "about.meta.location.label": "موقعیت:",
      "about.meta.location.value": "تهران، ایران",
      "about.meta.education.label": "تحصیلات:",
      "about.meta.education.value": "کارشناسی علوم کامپیوتر، دانشگاه صنعتی امیرکبیر",
      "about.meta.status.label": "وضعیت:",
      "about.meta.status.value": "آماده‌ی همکاری فریلنس",
      "about.p1":
        "من امیرحسین زمان‌زاده‌ام؛ دانشجوی علوم کامپیوتر و توسعه‌دهنده فول‌استک. روی محصولاتی سریع، مقیاس‌پذیر و کاربرمحور تمرکز می‌کنم و به معماری تمیز، ابزارهای مدرن و رابط‌هایی با حس پرداخت‌شده اهمیت می‌دهم.",
      "about.p2":
        "این نمونه کار بازتاب مسیر یادگیری من در وب، دسکتاپ، API و اتوماسیون است — و جایی برای شروع همکاری.",
      "about.skills": "مهارت‌ها",
      "contact.aria": "تماس",
      "contact.eyebrow": "بیایید در ارتباط باشیم",
      "contact.title": "صحبت کنیم.",
      "contact.intro": "پروژه یا موقعیتی در ذهن دارید؟ صحبت کنیم.",
      "contact.emailMe": "ایمیل بزنید",
      "contact.downloadCv": "دانلود رزومه",
      "contact.linkedin": "لینکدین",
      "contact.linkedin.sub": "شروع گفتگو",
      "contact.github": "گیت‌هاب",
      "contact.github.sub": "کد و مخازن",
      "contact.telegram": "تلگرام",
      "contact.telegram.sub": "گفتگوی سریع · @Amir_dev00",
      "contact.note": "آماده‌ی همکاری فریلنس هستم.",
      "category.Websites": "وب‌سایت‌ها",
      "category.Applications": "اپلیکیشن‌ها",
      "category.APIs & Backend": "API و بک‌اند",
      "category.Automation / Bots": "اتوماسیون / ربات",
    },
  };

  // Project body copy (titles & tech names stay from AZ_PROJECTS).
  var PROJECT_COPY = {
    en: {},
    fa: {
      "personal-portfolio-website": {
        shortDescription: "نمونه‌کار ریسپانسیو مدرن با Bootstrap",
        fullDescription:
          "یک وب‌سایت شخصی برای نمایش پروژه‌ها، مهارت‌ها و تجربه حرفه‌ای. با رابط تاریک تمیز و چیدمان کاملاً ریسپانسیو برای دسکتاپ، تبلت و موبایل.",
        category: "Websites",
        role: "توسعه‌دهنده فرانت‌اند و طراح",
      },
      "shopping-mall-desktop": {
        shortDescription: "اپلیکیشن خرید دسکتاپ با JavaFX و PostgreSQL",
        fullDescription:
          "اپلیکیشن دسکتاپ مرکز خرید با Java و JavaFX؛ شامل احراز هویت، مرور محصول، سبد خرید و پردازش سفارش با PostgreSQL و دسترسی مبتنی بر نقش.",
        category: "Applications",
        role: "توسعه‌دهنده فول‌استک",
      },
      "rest-api-microservice": {
        shortDescription: "API بک‌اند مقیاس‌پذیر با احراز هویت",
        fullDescription:
          "میکروسرویس بک‌اند با endpointهای REST برای مدیریت منابع، احراز هویت کاربر و اعتبارسنجی داده؛ با معماری ماژولار برای نگهداری و استقرار آسان‌تر.",
        category: "APIs & Backend",
        role: "توسعه‌دهنده بک‌اند",
      },
      "telegram-automation-bot": {
        shortDescription: "ربات تلگرام برای پیام و کارهای خودکار",
        fullDescription:
          "ربات تلگرامی که کارهای تکراری مثل اعلان‌ها، پیام‌های زمان‌بندی‌شده و جریان تعامل کاربر را خودکار می‌کند تا بهره‌وری و ارتباط ساده‌تر شود.",
        category: "Automation / Bots",
        role: "توسعه‌دهنده ربات",
      },
      "expense-tracker-app": {
        shortDescription: "اپلیکیشن دسکتاپ برای پیگیری هزینه‌های شخصی",
        fullDescription:
          "اپلیکیشن دسکتاپ برای ثبت هزینه‌های روزانه، دسته‌بندی مخارج و گزارش‌های ساده تا عادت‌های مالی بهتر دیده شوند.",
        category: "Applications",
        role: "توسعه‌دهنده اپلیکیشن",
      },
      "e-commerce-website": {
        shortDescription: "فروشگاه آنلاین با کاتالوگ و سبد خرید",
        fullDescription:
          "وب‌سایت فروشگاهی با فهرست محصولات، سبد خرید و تجربه پرداخت ساده؛ با تمرکز بر کاربردپذیری و الگوهای مدرن وب.",
        category: "Websites",
        role: "توسعه‌دهنده فول‌استک",
      },
      "jwt-auth-backend": {
        shortDescription: "سرویس احراز هویت امن با مدیریت نقش",
        fullDescription:
          "بک‌اند اختصاصی احراز هویت برای ثبت‌نام، ورود، تازه‌سازی توکن و دسترسی مبتنی بر نقش؛ به‌عنوان سرویس قابل استفاده مجدد برای اپلیکیشن‌های وب.",
        category: "APIs & Backend",
        role: "توسعه‌دهنده بک‌اند",
      },
      "web-scraping-bot": {
        shortDescription: "ربات جمع‌آوری داده و گزارش‌گیری خودکار",
        fullDescription:
          "ربات اتوماسیونی که داده هدف را از وب استخراج می‌کند، پردازش می‌نماید و گزارش‌های ساخت‌یافته برای پایش قیمت، تغییر محتوا یا پژوهش ارائه می‌دهد.",
        category: "Automation / Bots",
        role: "توسعه‌دهنده اتوماسیون",
      },
      "task-manager-app": {
        shortDescription: "اپ بهره‌وری برای مدیریت کار و پروژه",
        fullDescription:
          "اپلیکیشن دسکتاپ مدیریت کار برای فهرست کارها، اولویت‌بندی و پیگیری پیشرفت پروژه با وضوح و حداقل اصطکاک.",
        category: "Applications",
        role: "توسعه‌دهنده اپلیکیشن",
      },
      "landing-page-design": {
        shortDescription: "لندینگ مارکتینگ با تمرکز بر تبدیل",
        fullDescription:
          "طراحی لندینگ مدرن با پیام روشن، سلسله‌مراتب بصری قوی و چیدمان تبدیل‌محور برای استارتاپ‌ها و معرفی محصول.",
        category: "Websites",
        role: "طراح UI و توسعه‌دهنده فرانت‌اند",
      },
      "restful-api-service": {
        shortDescription: "API ماژولار برای یکپارچه‌سازی‌های خارجی",
        fullDescription:
          "API REST ماژولار برای اتصال به سرویس‌های بیرونی؛ شامل نسخه‌بندی، اعتبارسنجی ورودی و قالب پاسخ ساخت‌یافته.",
        category: "APIs & Backend",
        role: "توسعه‌دهنده API",
      },
      "discord-automation-bot": {
        shortDescription: "ربات مدیریت و نظارت جامعه در Discord",
        fullDescription:
          "ربات Discord برای خودکارسازی مدیریت جامعه؛ از پیام خوش‌آمد تا دستورات نظارت و اعلان‌های زمان‌بندی‌شده.",
        category: "Automation / Bots",
        role: "توسعه‌دهنده ربات",
      },
    },
  };

  function normalize(lang) {
    return lang === "fa" ? "fa" : "en";
  }

  function readStored() {
    try {
      return normalize(localStorage.getItem(STORAGE_KEY));
    } catch (err) {
      return "en";
    }
  }

  function writeStored(lang) {
    try {
      localStorage.setItem(STORAGE_KEY, lang);
    } catch (err) {}
  }

  var current = readStored();

  function t(key, vars) {
    var table = STRINGS[current] || STRINGS.en;
    var fallback = STRINGS.en;
    var value = table[key] != null ? table[key] : fallback[key];
    if (value == null) return key;
    if (!vars) return value;
    return String(value).replace(/\{(\w+)\}/g, function (_, name) {
      return vars[name] != null ? String(vars[name]) : "";
    });
  }

  function localizeProject(project) {
    if (!project) return project;
    var pack = (PROJECT_COPY[current] || {})[project.slug] || {};
    var categoryKey = "category." + (pack.category || project.category);
    return {
      slug: project.slug,
      title: project.title,
      shortDescription: pack.shortDescription || project.shortDescription,
      fullDescription: pack.fullDescription || project.fullDescription,
      category: t(categoryKey) !== categoryKey ? t(categoryKey) : pack.category || project.category,
      technologies: project.technologies,
      image: project.image,
      liveUrl: project.liveUrl,
      githubUrl: project.githubUrl,
      date: project.date,
      role: pack.role || project.role,
    };
  }

  function applyDocumentShell() {
    var html = document.documentElement;
    html.lang = current;
    html.dir = current === "fa" ? "rtl" : "ltr";
    html.dataset.azLang = current;
    html.classList.toggle("az-lang-fa", current === "fa");
    html.classList.toggle("az-lang-en", current === "en");
  }

  function homeSlideType() {
    // Persian must stay word/line-based so letters remain joined.
    return current === "fa" ? "lines, words" : "lines, words, chars";
  }

  function bridgeFaWordsAsChars(el) {
    if (!el || current !== "fa") return;
    el.querySelectorAll(".split-text__word").forEach(function (word) {
      word.classList.add("split-text__char");
    });
  }

  function revealSplitHeading(el) {
    if (!window.gsap || !el) return;
    var nodes = el.querySelectorAll(".split-text__line, .split-text__word, .split-text__char");
    if (!nodes.length) return;
    // Fade swiper already hides inactive slides; keep split units visible so a
    // mid-transition language switch cannot leave the active title stuck hidden.
    try {
      window.gsap.set(nodes, {
        autoAlpha: 1,
        x: 0,
        y: 0,
        xPercent: 0,
        yPercent: 0,
      });
    } catch (err) {}
  }

  function resplitHomeHeading(el, title) {
    if (window.gsap) {
      try {
        window.gsap.killTweensOf(
          el.querySelectorAll(".split-text__line, .split-text__word, .split-text__char")
        );
      } catch (err) {}
    }

    if (el._azSplitText && typeof el._azSplitText.revert === "function") {
      try {
        el._azSplitText.revert();
      } catch (err) {}
      el._azSplitText = null;
    }

    el.textContent = title;
    el.setAttribute("data-split-text-type", homeSlideType());
    el.setAttribute("data-split-text-set", current === "fa" ? "words" : "chars");

    var SplitText = window.SplitText;
    if (!SplitText) {
      el.classList.add("js-split-text");
      return;
    }

    var split = new SplitText(el, {
      type: homeSlideType(),
      linesClass: "split-text__line overflow",
      wordsClass: "split-text__word",
      charsClass: "split-text__char",
      reduceWhiteSpace: false,
    });
    el._azSplitText = split;
    el.classList.add("split-text");
    el.classList.remove("js-split-text");
    bridgeFaWordsAsChars(el);
    revealSplitHeading(el);
  }

  function applyHomeHeadings() {
    var root = document.getElementById("view-home");
    if (!root) return;

    root.querySelectorAll(".slider__heading[data-project-slug]").forEach(function (el) {
      var slug = el.getAttribute("data-project-slug");
      if (!slug) return;
      var title = t("home.slide." + slug);
      var type = homeSlideType();
      el.setAttribute("data-split-text-type", type);
      el.setAttribute("data-split-text-set", current === "fa" ? "words" : "chars");

      var awaitingRhye =
        el.classList.contains("js-split-text") &&
        !el.querySelector(".split-text__word, .split-text__char, .split-text__line");

      if (awaitingRhye) {
        el.textContent = title;
        return;
      }

      resplitHomeHeading(el, title);
    });
  }

  function scheduleHomeHeadingBridge() {
    var tries = 0;
    var timer = window.setInterval(function () {
      tries += 1;
      var headings = document.querySelectorAll(
        "#view-home .slider__heading[data-project-slug]"
      );
      var splitReady = true;
      headings.forEach(function (el) {
        var hasSplit = !!el.querySelector(
          ".split-text__word, .split-text__char, .split-text__line"
        );
        if (el.classList.contains("js-split-text") && !hasSplit) splitReady = false;
        if (hasSplit) bridgeFaWordsAsChars(el);
      });
      if (splitReady || tries > 60) window.clearInterval(timer);
    }, 50);
  }

  function applyNode(el) {
    if (!el || el.nodeType !== 1) return;
    // Preloader stays English-only; never apply translation hooks inside it.
    if (el.closest && el.closest("#js-preloader")) return;
    var key = el.getAttribute("data-i18n");
    if (key) {
      var vars = {};
      if (el.hasAttribute("data-i18n-n")) vars.n = el.getAttribute("data-i18n-n");
      if (el.hasAttribute("data-i18n-name")) vars.name = el.getAttribute("data-i18n-name");
      el.textContent = t(key, vars);
    }
    var attrMap = el.getAttribute("data-i18n-attr");
    if (attrMap) {
      attrMap.split(";").forEach(function (pair) {
        var parts = pair.split(":");
        if (parts.length < 2) return;
        var attr = parts[0].trim();
        var attrKey = parts[1].trim();
        if (!attr || !attrKey) return;
        var vars = {};
        if (el.hasAttribute("data-i18n-name")) vars.name = el.getAttribute("data-i18n-name");
        el.setAttribute(attr, t(attrKey, vars));
      });
    }
  }

  function applyAll(root) {
    var scope = root || document;
    applyDocumentShell();
    scope.querySelectorAll("[data-i18n], [data-i18n-attr]").forEach(applyNode);
    applyHomeHeadings();
    var meta = document.querySelector('meta[name="description"]');
    if (meta) meta.setAttribute("content", t("meta.description"));
    document.querySelectorAll(".az-lang-switch__btn").forEach(function (btn) {
      var lang = btn.getAttribute("data-lang");
      var active = lang === current;
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });
  }

  function setLang(next, opts) {
    var lang = normalize(next);
    if (lang === current && !(opts && opts.force)) return current;
    var keepScroll = !(opts && opts.resetScroll);
    var y = window.scrollY || 0;
    current = lang;
    writeStored(current);
    applyAll(document);
    listeners.forEach(function (fn) {
      try {
        fn(current);
      } catch (err) {}
    });
    window.dispatchEvent(
      new CustomEvent("az:langchange", {
        detail: { lang: current },
      })
    );
    if (keepScroll) {
      window.requestAnimationFrame(function () {
        window.scrollTo(0, y);
      });
    }
    return current;
  }

  function onChange(fn) {
    if (typeof fn === "function") listeners.push(fn);
  }

  function bindSwitcher(root) {
    var scope = root || document;
    scope.querySelectorAll("[data-lang]").forEach(function (btn) {
      if (btn.dataset.azLangBound === "1") return;
      btn.dataset.azLangBound = "1";
      btn.addEventListener("click", function (event) {
        event.preventDefault();
        setLang(btn.getAttribute("data-lang"));
      });
    });
  }

  // Apply shell ASAP; full text pass after DOM is ready.
  applyDocumentShell();

  function boot() {
    bindSwitcher(document);
    applyAll(document);
    scheduleHomeHeadingBridge();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  window.AZ_i18n = {
    t: t,
    getLang: function () {
      return current;
    },
    setLang: setLang,
    apply: applyAll,
    localizeProject: localizeProject,
    onChange: onChange,
    strings: STRINGS,
  };
})();
