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
      "title.ask": "Ask — Amirhossein Zamanzade",
      "title.contact": "Contact — Amirhossein Zamanzade | Developer",
      "title.project": "{name} — Amirhossein Zamanzade | Developer",
      "nav.primary": "Primary",
      "nav.work": "Work",
      "nav.about": "About",
      "nav.ask": "Ask",
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
      "home.arrows": "Background slides",
      "home.prev": "Previous slide",
      "home.next": "Next slide",
      "assistant.title": "Ask about Amirhossein",
      "assistant.prompt": "Ask about Amirhossein",
      "assistant.send": "Send question",
      "assistant.minimize": "Minimize chat",
      "assistant.newChat": "New chat",
      "assistant.transcript": "Conversation",
      "assistant.thinking": "Thinking",
      "assistant.q.projects": "What projects has he shipped?",
      "assistant.q.experience": "What’s his experience?",
      "assistant.q.stack": "Which technologies does he use?",
      "assistant.error.retry": "Something went wrong. Tap to retry or ask again.",
      "assistant.error.config": "The assistant isn’t configured yet — use Contact or Book a call.",
      "assistant.open.action": "Open {label}",
      "assistant.open.page": "page",
      "assistant.open.work": "Work",
      "assistant.open.about": "About",
      "assistant.open.contact": "Contact",
      "assistant.open.cv": "CV",
      "guide.launch": "Open Arin’s site tour",
      "guide.invite.title": "Hey, I’m Arin!",
      "guide.invite.body": "I’m the AI assistant on Amirhossein’s portfolio. Ask me about his work, or let me show you around.",
      "guide.start": "Start tour",
      "guide.notNow": "Not now",
      "guide.skip": "Skip",
      "guide.next": "Next",
      "guide.done": "Done",
      "guide.step": "{n} / {total}",
      "guide.step1.title": "Switch language anytime",
      "guide.step1.body": "English and فارسی stay here — tap to read the site in either language.",
      "guide.step2.title": "Ask Arin, your AI assistant",
      "guide.step2.body": "Type a question about Amirhossein’s projects, tech stack, or experience. I’ll use AI to answer right here on the site.",
      "guide.step3.title": "Move around with the dock",
      "guide.step3.body": "Use the dock to open Work, About, Ask or Contact, or jump back Home.",
      "work.aria": "Work",
      "work.eyebrow": "Selected work",
      "home.selectedWork": "Selected work",
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
      "about.skills.frontend": "Front-end",
      "about.skills.languages": "Programming Languages",
      "about.skills.backend": "Back-end & Database",
      "about.skills.other": "Other Skills",
      "contact.aria": "Contact",
      "contact.eyebrow": "Let’s connect",
      "contact.title": "Let’s work together",
      "contact.intro": "Have a project or role in mind? Book a call or send me an email.",
      "contact.emailMe": "Email me",
      "contact.downloadCv": "Download CV",
      "contact.linkedin": "LinkedIn",
      "contact.linkedin.sub": "Professional profile",
      "contact.github": "GitHub",
      "contact.github.sub": "Projects and code",
      "contact.telegram": "Telegram",
      "contact.telegram.sub": "Send a message",
      "contact.note": "Available for freelance collaborations.",
      "contact.bookCall": "Book a call",
      "booking.eyebrow": "Book a call",
      "booking.title": "Let’s Talk",
      "booking.intro": "A relaxed video call to talk through your project, role or idea. Pick a time that suits you — you’ll get a calendar invite right away.",
      "booking.duration": "{min} min",
      "booking.timezone": "Your time · {tz}",
      "booking.loading": "Loading live availability…",
      "booking.back": "Back to calendar",
      "booking.prev": "Previous month",
      "booking.next": "Next month",
      "booking.close": "Close",
      "booking.preferEmail": "Prefer email?",
      "booking.confirmed": "Your call is confirmed. You can download the calendar file below.",
      "booking.confirmed.email": "Your call is confirmed. A confirmation email is on its way, and you can download the calendar file below.",
      "booking.emailInstead": "Email me instead",
      "booking.openExternal": "Open scheduling page",
      "booking.setup.title": "Online booking is almost ready",
      "booking.setup.body": "Scheduling isn’t open yet. Send me an email and we’ll find a time that works for both of us.",
      "booking.setup.admin": "Admin: set your weekly hours under Admin → Bookings.",
      "booking.error.title": "The scheduler didn’t load",
      "booking.error.body": "Couldn’t reach the booking service. Please try again or email me.",
      "booking.step.date": "Choose a date",
      "booking.step.time": "Choose a time",
      "booking.step.details": "Your details",
      "booking.step.done": "You’re booked",
      "booking.continue": "Continue",
      "booking.confirm": "Confirm booking",
      "booking.name": "Full name",
      "booking.email": "Email",
      "booking.phone": "Phone (optional)",
      "booking.topic": "Meeting topic (optional)",
      "booking.message": "Message (optional)",
      "booking.privacy": "Your details are stored on this site only and used to schedule our call.",
      "booking.noSlots": "No times left on this day. Pick another date.",
      "booking.downloadIcs": "Add to calendar",
      "booking.reference": "Reference",
      "booking.unavailable": "That time was just taken. Please choose another slot.",
      "booking.disabled": "Online booking is paused. Email me and we’ll find a time.",
      "booking.role": "Software Engineer",
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
      "title.ask": "گفتگو — امیرحسین زمان‌زاده",
      "title.contact": "ارتباط — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "title.project": "{name} — امیرحسین زمان‌زاده | توسعه‌دهنده",
      "nav.primary": "ناوبری اصلی",
      "nav.work": "کارها",
      "nav.about": "درباره",
      "nav.ask": "گفتگو",
      "nav.home": "خانه",
      "nav.cv": "رزومه",
      "nav.contact": "ارتباط",
      "nav.tooltip.work": "کارها",
      "nav.tooltip.about": "درباره",
      "nav.tooltip.home": "خانه",
      "nav.tooltip.cv": "دانلود رزومه",
      "nav.tooltip.contact": "ارتباط",
      "nav.aria.cv": "دانلود رزومه",
      "lang.switcher": "زبان",
      "lang.en": "English",
      "lang.fa": "فارسی",
      "lang.aria.en": "Read this site in English",
      "lang.aria.fa": "نمایش سایت به فارسی",
      "home.profile.aria": "امیرحسین زمان‌زاده، مهندس نرم‌افزار",
      "home.profile.name": "امیرحسین زمان زاده",
      "home.profile.role": "مهندس نرم‌افزار",
      "home.arrows": "اسلاید پس‌زمینه",
      "home.prev": "اسلاید قبلی",
      "home.next": "اسلاید بعدی",
      "assistant.title": "دربارهٔ امیرحسین بپرس",
      "assistant.prompt": "دربارهٔ امیرحسین بپرس",
      "assistant.send": "ارسال پرسش",
      "assistant.minimize": "کوچک کردن گفتگو",
      "assistant.newChat": "گفتگوی تازه",
      "assistant.transcript": "گفتگو",
      "assistant.thinking": "در حال فکر کردن",
      "assistant.q.projects": "چه پروژه‌هایی انجام داده؟",
      "assistant.q.experience": "تجربه‌اش چیست؟",
      "assistant.q.stack": "از چه تکنولوژی‌هایی استفاده می‌کند؟",
      "assistant.error.retry": "مشکلی پیش آمد. دوباره بپرسید یا بعداً تلاش کنید.",
      "assistant.error.config": "دستیار هنوز پیکربندی نشده — از تماس یا رزرو تماس استفاده کنید.",
      "assistant.open.action": "باز کردن {label}",
      "assistant.open.page": "صفحه",
      "assistant.open.work": "نمونه‌کارها",
      "assistant.open.about": "درباره",
      "assistant.open.contact": "تماس",
      "assistant.open.cv": "رزومه",
      "guide.launch": "باز کردن راهنمای آرین",
      "guide.invite.title": "سلام، من آرین هستم!",
      "guide.invite.body": "من دستیار هوش مصنوعی سایت امیرحسینم. می‌تونی دربارهٔ کارهاش ازم بپرسی یا با هم یه دور کوتاه توی سایت بزنیم.",
      "guide.start": "شروع تور",
      "guide.notNow": "الان نه",
      "guide.skip": "رد کردن",
      "guide.next": "بعدی",
      "guide.done": "تمام",
      "guide.step": "{n} / {total}",
      "guide.step1.title": "هر وقت خواستید زبان را عوض کنید",
      "guide.step1.body": "انگلیسی و فارسی اینجاست — برای خواندن سایت به هر زبان ضربه بزنید.",
      "guide.step2.title": "گفتگو با آرین، دستیار هوش مصنوعی",
      "guide.step2.body": "توی این چت‌باکس دربارهٔ پروژه‌ها، تکنولوژی‌ها یا تجربهٔ امیرحسین سؤال بپرس. من با هوش مصنوعی همین‌جا جواب می‌دم.",
      "guide.step3.title": "با نوار پایین جابه‌جا شوید",
      "guide.step3.body": "از نوار پایین به کارها، درباره، گفتگو یا ارتباط برو یا به خانه برگرد.",
      "work.aria": "کارها",
      "work.eyebrow": "کارهای منتخب",
      "home.selectedWork": "پروژه‌های منتخب",
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
      "about.skills.frontend": "فرانت‌اند",
      "about.skills.languages": "زبان‌های برنامه‌نویسی",
      "about.skills.backend": "بک‌اند و پایگاه داده",
      "about.skills.other": "سایر مهارت‌ها",
      "contact.aria": "ارتباط",
      "contact.eyebrow": "بیایید در ارتباط باشیم",
      "contact.title": "بیایید همکاری کنیم",
      "contact.intro": "پروژه یا فرصت همکاری در نظر دارید؟ یک تماس رزرو کنید یا ایمیل بزنید.",
      "contact.emailMe": "ایمیل بزنید",
      "contact.downloadCv": "دانلود رزومه",
      "contact.linkedin": "لینکدین",
      "contact.linkedin.sub": "ارتباط حرفه‌ای",
      "contact.github": "گیت‌هاب",
      "contact.github.sub": "پروژه‌ها و کد",
      "contact.telegram": "تلگرام",
      "contact.telegram.sub": "ارسال پیام",
      "contact.note": "آماده‌ی همکاری فریلنس هستم.",
      "contact.bookCall": "رزرو تماس",
      "booking.eyebrow": "رزرو تماس",
      "booking.title": "گفت‌وگو کنیم",
      "booking.intro": "یک تماس تصویری کوتاه و صمیمی برای صحبت درباره‌ی پروژه، موقعیت شغلی یا ایده‌ی شما. زمانی را که برایتان مناسب است انتخاب کنید؛ دعوت‌نامه‌ی تقویم بلافاصله برایتان ارسال می‌شود.",
      "booking.duration": "{min} دقیقه",
      "booking.timezone": "به وقت شما · {tz}",
      "booking.loading": "در حال دریافت زمان‌های آزاد…",
      "booking.back": "بازگشت به تقویم",
      "booking.prev": "ماه قبل",
      "booking.next": "ماه بعد",
      "booking.close": "بستن",
      "booking.preferEmail": "ایمیل را ترجیح می‌دهید؟",
      "booking.confirmed": "تماس شما تأیید شد. می‌توانید فایل تقویم را از پایین دانلود کنید.",
      "booking.confirmed.email": "تماس شما تأیید شد. ایمیل تأیید هم ارسال شد و می‌توانید فایل تقویم را از پایین دانلود کنید.",
      "booking.emailInstead": "به جای آن ایمیل بزنید",
      "booking.openExternal": "باز کردن صفحه‌ی رزرو",
      "booking.setup.title": "رزرو آنلاین به‌زودی فعال می‌شود",
      "booking.setup.body": "فعلاً امکان رزرو آنلاین باز نیست. ایمیل بزنید تا زمانی مناسب برای هر دو پیدا کنیم.",
      "booking.setup.admin": "مدیر: ساعات هفتگی را در Admin → رزروها تنظیم کنید.",
      "booking.error.title": "تقویم رزرو بارگذاری نشد",
      "booking.error.body": "ارتباط با سرویس رزرو برقرار نشد. دوباره تلاش کنید یا ایمیل بزنید.",
      "booking.step.date": "انتخاب تاریخ",
      "booking.step.time": "انتخاب ساعت",
      "booking.step.details": "اطلاعات شما",
      "booking.step.done": "رزرو شد",
      "booking.continue": "ادامه",
      "booking.confirm": "تأیید رزرو",
      "booking.name": "نام و نام خانوادگی",
      "booking.email": "ایمیل",
      "booking.phone": "تلفن (اختیاری)",
      "booking.topic": "موضوع جلسه (اختیاری)",
      "booking.message": "پیام (اختیاری)",
      "booking.privacy": "اطلاعات شما فقط روی همین سایت ذخیره می‌شود و برای هماهنگی جلسه استفاده خواهد شد.",
      "booking.noSlots": "برای این روز زمانی باقی نمانده. روز دیگری انتخاب کنید.",
      "booking.downloadIcs": "افزودن به تقویم",
      "booking.reference": "کد پیگیری",
      "booking.unavailable": "این زمان همین حالا رزرو شد. لطفاً زمان دیگری انتخاب کنید.",
      "booking.disabled": "رزرو آنلاین موقتاً غیرفعال است. ایمیل بزنید تا زمانی پیدا کنیم.",
      "booking.role": "مهندس نرم‌افزار",
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
    var meta = document.querySelector('meta[name="description"]');
    if (meta && !window.AZ_ASSETS) meta.setAttribute("content", t("meta.description"));
    document.querySelectorAll(".az-lang-switch__btn").forEach(function (btn) {
      var lang = btn.getAttribute("data-lang");
      var active = lang === current;
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });
  }

  function ensureFaFont() {
    try {
      if (document.querySelector('link[data-az-font="estedad"]')) return;
      var manrope = document.querySelector('link[href*="manrope-variable.woff2"]');
      var href = "assets/fonts/estedad/estedad-variable.woff2";
      if (manrope) {
        href = String(manrope.getAttribute("href") || "").replace(
          /manrope\/manrope-variable\.woff2.*$/,
          "estedad/estedad-variable.woff2"
        ) || href;
      }
      var link = document.createElement("link");
      link.rel = "preload";
      link.as = "font";
      link.type = "font/woff2";
      link.crossOrigin = "anonymous";
      link.href = href;
      link.setAttribute("data-az-font", "estedad");
      document.head.appendChild(link);
    } catch (err) {}
  }

  function setLang(next, opts) {
    var lang = normalize(next);
    if (lang === current && !(opts && opts.force)) return current;
    var keepScroll = !(opts && opts.resetScroll);
    var y = window.scrollY || 0;
    current = lang;
    if (current === "fa") ensureFaFont();
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
      // Pointer activation should not leave a sticky focus ring; keyboard keeps focus.
      btn.addEventListener("pointerdown", function (event) {
        if (event.pointerType === "mouse" || event.pointerType === "touch" || event.pointerType === "pen") {
          btn.dataset.azPointerActivate = "1";
        }
      });
      btn.addEventListener("click", function (event) {
        event.preventDefault();
        var next = normalize(btn.getAttribute("data-lang"));
        if (window.AZ_ASSETS && window.AZ_nav) {
          if (next !== current) window.dispatchEvent(new CustomEvent("az:langrequest", { detail: { lang: next } }));
        } else setLang(next);
        if (btn.dataset.azPointerActivate === "1") {
          btn.blur();
          delete btn.dataset.azPointerActivate;
        }
      });
    });
  }

  function mergeBootstrapStrings() {
    var boot = window.AZ_BOOTSTRAP;
    if (!boot || !boot.strings) return;
    ["en", "fa"].forEach(function (lang) {
      if (!boot.strings[lang]) return;
      STRINGS[lang] = Object.assign({}, STRINGS[lang] || {}, boot.strings[lang]);
    });
    if (boot.lang) current = normalize(boot.lang);
  }

  // Apply shell ASAP; full text pass after DOM is ready.
  mergeBootstrapStrings();
  applyDocumentShell();

  function boot() {
    mergeBootstrapStrings();
    bindSwitcher(document);
    applyAll(document);
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
