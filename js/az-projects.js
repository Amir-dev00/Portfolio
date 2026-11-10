/* Project catalog for in-app Work views. Images map to assets/img/amir.
   Prefer CMS bootstrap payload when present. */
window.AZ_PROJECTS = (window.AZ_BOOTSTRAP && Array.isArray(window.AZ_BOOTSTRAP.projects) && window.AZ_BOOTSTRAP.projects.length)
  ? window.AZ_BOOTSTRAP.projects
  : [
  {
    slug: "personal-portfolio-website",
    title: "Personal Portfolio Website",
    shortDescription: "Modern responsive portfolio built with Bootstrap",
    fullDescription:
      "A personal portfolio website designed to showcase projects, skills, and professional experience. Built with a clean, dark-themed UI and fully responsive layout for desktop, tablet, and mobile devices.",
    category: "Websites",
    technologies: ["HTML5", "CSS3", "Bootstrap 5", "JavaScript", "AOS", "Swiper"],
    image: "assets/img/amir/portfolio-w1.webp",
    liveUrl: "https://github.com/Amir-dev00/Portfolio",
    githubUrl: "https://github.com/Amir-dev00",
    date: "2025",
    role: "Front-End Developer & Designer",
  },
  {
    slug: "shopping-mall-desktop",
    title: "Shopping Mall Desktop",
    shortDescription: "JavaFX desktop shopping application with PostgreSQL",
    fullDescription:
      "A desktop-based shopping mall application developed using Java and JavaFX. It covers authentication, product browsing, cart management, and order processing with PostgreSQL integration and role-based access.",
    category: "Applications",
    technologies: ["Java 21", "JavaFX 21", "Maven", "PostgreSQL", "JDBC"],
    image: "assets/img/amir/portfolio-1.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2025",
    role: "Full-Stack Developer",
  },
  {
    slug: "rest-api-microservice",
    title: "REST API Microservice",
    shortDescription: "Scalable backend API with authentication",
    fullDescription:
      "A backend microservice exposing RESTful endpoints for resource management, user authentication, and data validation. Designed with modular architecture for easy maintenance and deployment.",
    category: "APIs & Backend",
    technologies: ["Node.js", "Express", "MongoDB", "JWT", "REST"],
    image: "assets/img/amir/portfolio-7.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Back-End Developer",
  },
  {
    slug: "telegram-automation-bot",
    title: "Telegram Automation Bot",
    shortDescription: "Automated messaging and task bot for Telegram",
    fullDescription:
      "A Telegram bot that automates repetitive tasks such as notifications, scheduled messages, and user interaction workflows. Built to improve productivity and streamline communication.",
    category: "Automation / Bots",
    technologies: ["Python", "python-telegram-bot", "SQLite", "APScheduler"],
    image: "assets/img/amir/portfolio-4.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Bot Developer",
  },
  {
    slug: "expense-tracker-app",
    title: "Expense Tracker App",
    shortDescription: "Desktop app for personal finance tracking",
    fullDescription:
      "A desktop application for tracking daily expenses, categorizing spending, and generating simple reports to help users understand financial habits over time.",
    category: "Applications",
    technologies: ["Java", "JavaFX", "SQLite", "Maven"],
    image: "assets/img/amir/portfolio-2.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Application Developer",
  },
  {
    slug: "e-commerce-website",
    title: "E-Commerce Website",
    shortDescription: "Online store with product catalog and cart",
    fullDescription:
      "A full-featured e-commerce website with product listings, shopping cart functionality, and a streamlined checkout experience focused on usability and modern web patterns.",
    category: "Websites",
    technologies: ["HTML", "CSS", "JavaScript", "Bootstrap", "PHP"],
    image: "assets/img/amir/portfolio-11.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Full-Stack Developer",
  },
  {
    slug: "jwt-auth-backend",
    title: "JWT Auth Backend",
    shortDescription: "Secure authentication service with role management",
    fullDescription:
      "A dedicated authentication backend providing user registration, login, token refresh, and role-based authorization as a reusable service for web applications.",
    category: "APIs & Backend",
    technologies: ["Node.js", "Express", "PostgreSQL", "JWT", "bcrypt"],
    image: "assets/img/amir/portfolio-8.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Back-End Developer",
  },
  {
    slug: "web-scraping-bot",
    title: "Web Scraping Bot",
    shortDescription: "Automated data collection and reporting bot",
    fullDescription:
      "An automation bot that scrapes targeted web data, processes it, and delivers formatted reports for monitoring prices, content changes, or research collection.",
    category: "Automation / Bots",
    technologies: ["Python", "BeautifulSoup", "Requests", "Pandas"],
    image: "assets/img/amir/portfolio-5.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2024",
    role: "Automation Developer",
  },
  {
    slug: "task-manager-app",
    title: "Task Manager App",
    shortDescription: "Productivity app for task and project management",
    fullDescription:
      "A task management desktop application for organizing to-do lists, setting priorities, and tracking project progress with clarity and minimal friction.",
    category: "Applications",
    technologies: ["Java", "JavaFX", "SQLite", "MVC"],
    image: "assets/img/amir/portfolio-3.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2023",
    role: "Application Developer",
  },
  {
    slug: "landing-page-design",
    title: "Landing Page Design",
    shortDescription: "High-conversion marketing landing page",
    fullDescription:
      "A modern landing page design focused on clear messaging, strong visual hierarchy, and conversion-oriented layout for startups and product launches.",
    category: "Websites",
    technologies: ["HTML", "CSS", "Bootstrap", "JavaScript", "Figma"],
    image: "assets/img/amir/portfolio-12.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2023",
    role: "UI Designer & Front-End Developer",
  },
  {
    slug: "restful-api-service",
    title: "RESTful API Service",
    shortDescription: "Modular API for third-party integrations",
    fullDescription:
      "A modular REST API designed for integration with external services, including versioning, input validation, and structured response formats.",
    category: "APIs & Backend",
    technologies: ["Python", "FastAPI", "PostgreSQL", "Pydantic", "Docker"],
    image: "assets/img/amir/portfolio-9.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2023",
    role: "API Developer",
  },
  {
    slug: "discord-automation-bot",
    title: "Discord Automation Bot",
    shortDescription: "Community management and moderation bot",
    fullDescription:
      "A Discord bot that automates community management tasks including welcome messages, moderation commands, and scheduled announcements.",
    category: "Automation / Bots",
    technologies: ["JavaScript", "Discord.js", "Node.js", "MongoDB"],
    image: "assets/img/amir/portfolio-6.webp",
    liveUrl: null,
    githubUrl: "https://github.com/Amir-dev00",
    date: "2023",
    role: "Bot Developer",
  },
];

window.AZ_getProjectBySlug = function (slug) {
  return (window.AZ_PROJECTS || []).find(function (project) {
    return project.slug === slug;
  });
};
