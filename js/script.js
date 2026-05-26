// ========================================
// お問い合わせフォーム送信処理
// ========================================
document.addEventListener('DOMContentLoaded', () => {
  const initDeviceClass = () => {
    const touchQuery = window.matchMedia('(hover: none) and (pointer: coarse)');
    const setClass = () => {
      document.documentElement.classList.toggle(
        'is-touch-device',
        touchQuery.matches || navigator.maxTouchPoints > 0
      );
    };

    setClass();

    if (typeof touchQuery.addEventListener === 'function') {
      touchQuery.addEventListener('change', setClass);
    }
  };

  const initSiteMenu = () => {
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.getElementById('siteMenu');
    const closeTargets = document.querySelectorAll('[data-menu-close], .site-menu a');

    if (!toggle || !menu) {
      return;
    }

    const setMenu = (isOpen) => {
      document.documentElement.classList.toggle('is-menu-open', isOpen);
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
    };

    toggle.addEventListener('click', () => {
      setMenu(!document.documentElement.classList.contains('is-menu-open'));
    });

    closeTargets.forEach((target) => {
      target.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('pointerdown', (event) => {
      if (!document.documentElement.classList.contains('is-menu-open')) {
        return;
      }

      if (menu.contains(event.target) || toggle.contains(event.target)) {
        return;
      }

      setMenu(false);
    });

    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setMenu(false);
      }
    });
  };

  const initIdleCursor = () => {
    const isFinePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (!isFinePointer) {
      return;
    }

    let idleTimer;
    const showCursor = () => {
      document.documentElement.classList.remove('is-cursor-idle');
      window.clearTimeout(idleTimer);
      idleTimer = window.setTimeout(() => {
        document.documentElement.classList.add('is-cursor-idle');
      }, 900);
    };

    window.addEventListener('pointermove', showCursor, { passive: true });
    window.addEventListener('pointerdown', showCursor, { passive: true });
    window.addEventListener('blur', () => {
      document.documentElement.classList.remove('is-cursor-idle');
      window.clearTimeout(idleTimer);
    });

    showCursor();
  };

  const initCursorSparkles = () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
      return;
    }

    const isFinePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    let lastSparkleAt = 0;
    let lastTouchPoint = null;
    let activeSparkles = 0;
    const maxSparkles = isFinePointer ? 12 : 7;

    const createSparkle = (x, y) => {
      if (activeSparkles >= maxSparkles) {
        return;
      }

      activeSparkles += 1;
      const sparkle = document.createElement('span');
      const useBrandColor = Math.random() < 0.28;
      const size = Math.round((useBrandColor ? 3 : 4) + Math.random() * (useBrandColor ? 3 : 5));
      const driftX = Math.round((Math.random() - 0.5) * 28);
      const driftY = Math.round(-12 - Math.random() * 20);
      const color = useBrandColor ? 'rgba(37, 37, 139, 0.78)' : '#ffffff';

      sparkle.className = useBrandColor ? 'cursor-sparkle is-brand-sparkle' : 'cursor-sparkle';
      sparkle.style.left = `${x}px`;
      sparkle.style.top = `${y}px`;
      sparkle.style.width = `${size}px`;
      sparkle.style.height = `${size}px`;
      sparkle.style.background = color;
      sparkle.style.color = color;
      sparkle.style.setProperty('--sparkle-x', `${driftX}px`);
      sparkle.style.setProperty('--sparkle-y', `${driftY}px`);

      document.body.appendChild(sparkle);
      sparkle.addEventListener('animationend', () => {
        activeSparkles = Math.max(0, activeSparkles - 1);
        sparkle.remove();
      }, { once: true });
    };

    const emitSparklesAt = (x, y, interval = 38) => {
      const now = performance.now();

      if (now - lastSparkleAt < interval) {
        return;
      }

      lastSparkleAt = now;
      createSparkle(x, y);
    };

    const emitSparkles = (event, interval = 38) => {
      emitSparklesAt(event.clientX, event.clientY, interval);
    };

    const updateTouchPoint = (event) => {
      const touch = event.touches && event.touches[0];

      if (!touch) {
        return;
      }

      lastTouchPoint = {
        x: touch.clientX,
        y: touch.clientY
      };
    };

    if (isFinePointer) {
      window.addEventListener('pointermove', (event) => {
        emitSparkles(event, 120);
      }, { passive: true });
    } else {
      window.addEventListener('pointerdown', (event) => {
        createSparkle(event.clientX, event.clientY);
      }, { passive: true });

      window.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch' || event.pointerType === 'pen') {
          emitSparkles(event, 240);
        }
      }, { passive: true });

      window.addEventListener('touchstart', (event) => {
        updateTouchPoint(event);

        if (lastTouchPoint) {
          emitSparklesAt(lastTouchPoint.x, lastTouchPoint.y, 0);
        }
      }, { passive: true });

      window.addEventListener('touchmove', (event) => {
        updateTouchPoint(event);

        if (lastTouchPoint) {
          emitSparklesAt(lastTouchPoint.x, lastTouchPoint.y, 240);
        }
      }, { passive: true });

      window.addEventListener('scroll', () => {
        if (!lastTouchPoint) {
          return;
        }

        emitSparklesAt(
          lastTouchPoint.x + (Math.random() - 0.5) * 18,
          lastTouchPoint.y + (Math.random() - 0.5) * 18,
          360
        );
      }, { passive: true });
    }
  };

  const initCardSparkles = () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
      return;
    }

    const isFinePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const cardSelector = '.news-card, .featured-liver-card, .ranking-card';
    const lastSparkleByCard = new WeakMap();
    let activeSparkles = 0;
    const maxSparkles = isFinePointer ? 18 : 9;

    const createCardSparkle = (card, clientX, clientY, interval = 110) => {
      if (!card || activeSparkles >= maxSparkles) {
        return;
      }

      const now = performance.now();
      const lastSparkleAt = lastSparkleByCard.get(card) || 0;

      if (now - lastSparkleAt < interval) {
        return;
      }

      lastSparkleByCard.set(card, now);

      const rect = card.getBoundingClientRect();
      const localX = clientX - rect.left;
      const localY = clientY - rect.top;

      if (localX < 0 || localY < 0 || localX > rect.width || localY > rect.height) {
        return;
      }

      activeSparkles += 1;
      card.classList.add('is-card-sparkling');

      const sparkle = document.createElement('span');
      const size = Math.round(4 + Math.random() * 5);
      const driftX = Math.round((Math.random() - 0.5) * 22);
      const driftY = Math.round(-10 - Math.random() * 18);

      sparkle.className = 'card-sparkle';
      sparkle.style.setProperty('--card-sparkle-left', `${localX}px`);
      sparkle.style.setProperty('--card-sparkle-top', `${localY}px`);
      sparkle.style.setProperty('--card-sparkle-size', `${size}px`);
      sparkle.style.setProperty('--card-sparkle-x', `${driftX}px`);
      sparkle.style.setProperty('--card-sparkle-y', `${driftY}px`);
      card.appendChild(sparkle);

      sparkle.addEventListener('animationend', () => {
        activeSparkles = Math.max(0, activeSparkles - 1);
        sparkle.remove();
      }, { once: true });

      window.setTimeout(() => {
        card.classList.remove('is-card-sparkling');
      }, 820);
    };

    const findSparkleCard = (target) => {
      if (!(target instanceof Element)) {
        return null;
      }

      return target.closest(cardSelector);
    };

    document.addEventListener('pointermove', (event) => {
      const card = findSparkleCard(event.target);

      if (!card) {
        return;
      }

      createCardSparkle(card, event.clientX, event.clientY, isFinePointer ? 180 : 260);
    }, { passive: true });

    document.addEventListener('pointerdown', (event) => {
      const card = findSparkleCard(event.target);

      if (!card) {
        return;
      }

      createCardSparkle(card, event.clientX, event.clientY, 0);
    }, { passive: true });
  };

  const initHeadingReveal = () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const headings = document.querySelectorAll('.section-heading, .about-copy.heading-inverted, .page-header');

    if (!headings.length) {
      return;
    }

    headings.forEach((heading) => heading.classList.add('reveal-heading'));

    if (reduceMotion || !('IntersectionObserver' in window)) {
      headings.forEach((heading) => heading.classList.add('is-visible'));
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px -8% 0px',
      threshold: 0.08
    });

    headings.forEach((heading) => observer.observe(heading));
  };

  const initAuditionFaq = () => {
    const faqButtons = document.querySelectorAll('.audition-faq-question');

    faqButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const item = button.closest('.audition-faq-item');

        if (!item) {
          return;
        }

        const isOpen = item.classList.toggle('is-open');
        button.setAttribute('aria-expanded', String(isOpen));
      });
    });
  };

  const initFloatingEntryCta = () => {
    if (document.querySelector('.floating-entry-cta')) {
      return;
    }

    const cta = document.createElement('a');
    cta.className = 'floating-entry-cta';
    cta.href = 'audition.html';
    cta.setAttribute('aria-label', 'ライバー募集ページを見る');
    cta.innerHTML = `
      <span class="floating-entry-cta__eyebrow">LIVER ENTRY</span>
      <span class="floating-entry-cta__title">ライバー募集</span>
      <span class="floating-entry-cta__arrow" aria-hidden="true">→</span>
    `;

    document.body.appendChild(cta);

    const hero = document.querySelector('.hero');
    const footer = document.querySelector('.footer');

    if (!hero && !footer) {
      return;
    }

    let ticking = false;

    const updateVisibility = () => {
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const heroRevealOffset = Math.min(180, Math.max(96, viewportHeight * 0.16));
      const heroRect = hero ? hero.getBoundingClientRect() : null;
      const footerRect = footer ? footer.getBoundingClientRect() : null;
      const isOverHero = heroRect ? heroRect.bottom > viewportHeight - heroRevealOffset : false;
      const isOverFooter = footerRect ? footerRect.top < viewportHeight && footerRect.bottom > 0 : false;
      const shouldHide = isOverHero || isOverFooter;

      cta.classList.toggle('is-floating-entry-hidden', shouldHide);
      ticking = false;
    };

    const requestUpdate = () => {
      if (ticking) {
        return;
      }

      ticking = true;
      window.requestAnimationFrame(updateVisibility);
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    updateVisibility();
  };

  initDeviceClass();
  initSiteMenu();
  initIdleCursor();
  initCursorSparkles();
  initCardSparkles();
  initHeadingReveal();
  initAuditionFaq();
  initFloatingEntryCta();

  const rankingPlatformTabs = document.querySelectorAll('[data-ranking-platform]');
  const rankingPlatformPanels = document.querySelectorAll('[data-ranking-platform-panel]');
  const rankingListTabs = document.querySelectorAll('[data-ranking-list]');

  rankingPlatformTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.rankingPlatform;

      rankingPlatformTabs.forEach((item) => {
        item.classList.toggle('is-active', item === tab);
      });

      rankingPlatformPanels.forEach((panel) => {
        panel.classList.toggle('is-active', panel.dataset.rankingPlatformPanel === target);
      });
    });
  });

  rankingListTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.rankingList;
      const platformPanel = tab.closest('[data-ranking-platform-panel]');

      if (!platformPanel) {
        return;
      }

      platformPanel.querySelectorAll('[data-ranking-list]').forEach((item) => {
        item.classList.toggle('is-active', item === tab);
      });

      platformPanel.querySelectorAll('[data-ranking-list-panel]').forEach((panel) => {
        panel.classList.toggle('is-active', panel.dataset.rankingListPanel === target);
      });
    });
  });

  const newsItems = window.carveout17LiveNews || [];
  const eventItems = window.carveoutOfficeEventNews || [];
  const interviewItems = window.carveoutInterviews || [];
  const byNewest = (a, b) => new Date(b.datetime || 0) - new Date(a.datetime || 0);
  const sortByNewest = (items) => [...items].sort(byNewest);

  const getContentId = (url) => {
    const match = String(url || '').match(/\/(\d+)\/?$/);
    return match ? match[1] : '';
  };

  const getNewsDetailUrl = (item) => `news-detail.html?id=${getContentId(item.url)}`;
  const getInterviewDetailUrl = (item) => `interview-detail.html?id=${item.id}`;
  const loadDetailHtml = (src) => new Promise((resolve) => {
    if (!src) {
      resolve('');
      return;
    }

    window.carveoutCurrentDetailHtml = '';
    const script = document.createElement('script');
    script.src = src;
    script.onload = () => resolve(window.carveoutCurrentDetailHtml || '');
    script.onerror = () => resolve('');
    document.head.appendChild(script);
  });

  const createNewsCard = (item) => {
    const card = document.createElement('a');
    card.className = 'news-card';
    card.href = getNewsDetailUrl(item);

    if (item.image) {
      const image = document.createElement('img');
      image.src = item.image;
      image.alt = item.title;
      image.loading = 'lazy';
      image.decoding = 'async';
      card.appendChild(image);
    } else {
      const placeholder = document.createElement('div');
      placeholder.className = 'news-card-placeholder';
      placeholder.textContent = 'CARVEOUT';
      card.appendChild(placeholder);
    }

    const body = document.createElement('div');
    const time = document.createElement('time');
    time.dateTime = item.datetime;
    time.textContent = item.date;

    const title = document.createElement('h3');
    title.textContent = item.title;

    const detail = document.createElement('span');
    detail.className = 'news-card-detail';
    detail.textContent = '詳しく見る';

    body.append(time, title, detail);
    card.appendChild(body);

    return card;
  };

  const createInterviewCard = (item) => {
    const card = document.createElement('a');
    card.className = 'interview-list-card';
    card.href = getInterviewDetailUrl(item);

    const image = document.createElement('img');
    image.src = item.image;
    image.alt = item.title;
    image.loading = 'lazy';
    image.decoding = 'async';

    const body = document.createElement('div');
    const time = document.createElement('time');
    time.dateTime = item.datetime;
    time.textContent = item.date;

    const title = document.createElement('h3');
    title.textContent = item.title;

    body.append(time, title);
    card.append(image, body);

    return card;
  };

  const createDetailMarkup = (item, backHref, backText, detailHtml = '') => {
    const fragment = document.createDocumentFragment();

    if (!item) {
      const title = document.createElement('h2');
      title.textContent = '記事が見つかりませんでした';

      const text = document.createElement('p');
      text.textContent = '一覧ページからもう一度記事を選択してください。';

      const back = document.createElement('a');
      back.className = 'btn btn-secondary detail-back-link';
      back.href = backHref;
      back.textContent = backText;

      fragment.append(title, text, back);
      return fragment;
    }

    if (item.image) {
      const image = document.createElement('img');
      image.className = 'detail-hero-image';
      image.src = item.image;
      image.alt = item.title;
      fragment.appendChild(image);
    }

    const meta = document.createElement('time');
    meta.className = 'detail-date';
    meta.dateTime = item.datetime;
    meta.textContent = item.date;

    const title = document.createElement('h2');
    title.textContent = item.title;

    const body = document.createElement('div');
    body.className = 'detail-body';

    if (detailHtml) {
      body.innerHTML = detailHtml;
      body.querySelectorAll('a[href^="http"]').forEach((link) => {
        link.target = '_blank';
        link.rel = 'noopener';
      });
    } else {
      const paragraphs = item.body && item.body.length
        ? item.body
        : [
            'CARVEOUT所属ライバー・クリエイターの活動情報をお知らせします。',
            item.title
          ];

      paragraphs.forEach((paragraph) => {
        const text = document.createElement('p');
        text.textContent = paragraph;
        body.appendChild(text);
      });
    }

    const back = document.createElement('a');
    back.className = 'btn btn-secondary detail-back-link';
    back.href = backHref;
    back.textContent = backText;

    fragment.append(meta, title, body, back);
    return fragment;
  };

  const allNewsGrid = document.getElementById('allNewsGrid');
  const newsYearTabs = document.getElementById('newsYearTabs');
  const officeEventGrid = document.getElementById('officeEventGrid');
  const allEventGrid = document.getElementById('allEventGrid');
  const eventYearTabs = document.getElementById('eventYearTabs');
  const getNewsYear = (dateText) => Number(dateText.slice(0, 4));

  if (allNewsGrid && Array.isArray(newsItems)) {
    const years = [...new Set(newsItems.map((item) => getNewsYear(item.datetime)))];
    let activeYear = years[0];

    const renderNews = (year) => {
      allNewsGrid.innerHTML = '';
      const fragment = document.createDocumentFragment();

      newsItems
        .filter((item) => getNewsYear(item.datetime) === year)
        .forEach((item) => {
          fragment.appendChild(createNewsCard(item));
        });

      allNewsGrid.appendChild(fragment);
    };

    if (newsYearTabs) {
      years.forEach((year) => {
        const count = newsItems.filter((item) => getNewsYear(item.datetime) === year).length;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = year === activeYear ? 'news-tab is-active' : 'news-tab';
        button.textContent = `${year}年 (${count})`;
        button.setAttribute('aria-pressed', String(year === activeYear));

        button.addEventListener('click', () => {
          activeYear = year;
          newsYearTabs.querySelectorAll('.news-tab').forEach((tab) => {
            const isActive = tab === button;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-pressed', String(isActive));
          });
          renderNews(activeYear);
        });

        newsYearTabs.appendChild(button);
      });
    }

    renderNews(activeYear);
  }

  if (officeEventGrid) {
    const topEventItems = eventItems.length
      ? sortByNewest(eventItems)
      : sortByNewest(newsItems.filter((item) => /事務所(?:内)?イベント/.test(item.title)));
    const fragment = document.createDocumentFragment();

    topEventItems.slice(0, 5).forEach((item) => {
      fragment.appendChild(createNewsCard(item));
    });

    officeEventGrid.appendChild(fragment);
  }

  if (allEventGrid) {
    const years = [...new Set(eventItems.map((item) => getNewsYear(item.datetime)))];
    let activeYear = years[0];

    const renderEvents = (year) => {
      allEventGrid.innerHTML = '';
      const fragment = document.createDocumentFragment();

      eventItems
        .filter((item) => getNewsYear(item.datetime) === year)
        .forEach((item) => {
          fragment.appendChild(createNewsCard(item));
        });

      allEventGrid.appendChild(fragment);
    };

    if (eventYearTabs) {
      years.forEach((year) => {
        const count = eventItems.filter((item) => getNewsYear(item.datetime) === year).length;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = year === activeYear ? 'news-tab is-active' : 'news-tab';
        button.textContent = `${year}年 (${count})`;
        button.setAttribute('aria-pressed', String(year === activeYear));

        button.addEventListener('click', () => {
          activeYear = year;
          eventYearTabs.querySelectorAll('.news-tab').forEach((tab) => {
            const isActive = tab === button;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-pressed', String(isActive));
          });
          renderEvents(activeYear);
        });

        eventYearTabs.appendChild(button);
      });
    }

    renderEvents(activeYear);
  }

  const latestNewsTrack = document.getElementById('latestNewsTrack');

  if (latestNewsTrack && Array.isArray(newsItems)) {
    const latestItems = sortByNewest(newsItems).slice(0, 5);
    const fragment = document.createDocumentFragment();

    latestItems.forEach((item) => {
      fragment.appendChild(createNewsCard(item));
    });

    latestNewsTrack.appendChild(fragment);
  }

  const newsDetail = document.getElementById('newsDetail');

  if (newsDetail) {
    const id = new URLSearchParams(window.location.search).get('id');
    const item = [...newsItems, ...eventItems].find((contentItem) => getContentId(contentItem.url) === id);
    const isEventDetail = eventItems.some((contentItem) => getContentId(contentItem.url) === id);
    const detailPageEyebrow = document.getElementById('detailPageEyebrow');
    const detailPageTitle = document.getElementById('detailPageTitle');

    if (isEventDetail) {
      if (detailPageEyebrow) {
        detailPageEyebrow.textContent = 'EVENT';
      }

      if (detailPageTitle) {
        detailPageTitle.textContent = 'イベント詳細';
      }

      document.title = 'イベント詳細 | CARVEOUT';
    }

    loadDetailHtml(isEventDetail ? `js/event-details/${id}.js` : `js/news-details/${id}.js`).then((detailHtml) => {
      newsDetail.appendChild(createDetailMarkup(
        item,
        isEventDetail ? 'events.html' : 'news.html',
        isEventDetail ? 'イベント一覧へ戻る' : 'ニュース一覧へ戻る',
        detailHtml
      ));
    });
  }

  const interviewArchiveList = document.getElementById('interviewArchiveList');

  if (interviewArchiveList && Array.isArray(interviewItems)) {
    const fragment = document.createDocumentFragment();

    interviewItems.forEach((item) => {
      fragment.appendChild(createInterviewCard(item));
    });

    interviewArchiveList.appendChild(fragment);
  }

  const interviewDetail = document.getElementById('interviewDetail');

  if (interviewDetail) {
    const id = new URLSearchParams(window.location.search).get('id');
    const item = interviewItems.find((interviewItem) => interviewItem.id === id);
    loadDetailHtml(`js/interview-details/${id}.js`).then((detailHtml) => {
      interviewDetail.appendChild(createDetailMarkup(item, 'interview.html', 'インタビュー一覧へ戻る', detailHtml));
    });
  }

  const liverPlatformMeta = {
    '17LIVE': {
      icon: 'https://ccarveout.jp/wp-content/themes/carveout_2/images/v2/icon_big_17.png',
      label: '17LIVE',
      className: 'is-17live'
    },
    BIGOLIVE: {
      icon: 'https://ccarveout.jp/wp-content/themes/carveout_2/images/v2/icon_big_bigo.png',
      label: 'BIGOLIVE',
      className: 'is-bigo'
    },
    TikTokLIVE: {
      icon: 'https://ccarveout.jp/wp-content/themes/carveout_2/images/v2/icon_big_t.png',
      label: 'TikTokLIVE',
      className: 'is-tiktok'
    },
    Pococha: {
      icon: 'https://ccarveout.jp/wp-content/themes/carveout_2/images/v2/icon_big_p.png',
      label: 'Pococha',
      className: 'is-pococha'
    }
  };

  const getLiverPlatform = (category) => liverPlatformMeta[category] || liverPlatformMeta['17LIVE'];

  const createLiverCard = (item) => {
    const platform = getLiverPlatform(item.category);
    const card = document.createElement('article');
    card.className = `featured-liver-card ${platform.className}`;

    const image = document.createElement('img');
    image.src = item.image;
    image.alt = item.name;
    image.loading = 'lazy';
    image.decoding = 'async';

    const body = document.createElement('div');
    body.className = 'featured-liver-body';

    const category = document.createElement('span');
    category.className = `liver-category ${platform.className}`;
    category.textContent = item.category;

    const name = document.createElement('h3');
    name.className = 'liver-name';
    name.textContent = item.name;

    const actions = document.createElement('div');
    actions.className = 'liver-social-links';

    const liveLink = document.createElement('a');
    liveLink.href = item.url;
    liveLink.target = '_blank';
    liveLink.rel = 'noopener';
    liveLink.setAttribute('aria-label', `${item.name}の${platform.label}プロフィール`);

    const liveIcon = document.createElement('img');
    liveIcon.src = platform.icon;
    liveIcon.alt = platform.label;
    liveIcon.loading = 'lazy';
    liveIcon.decoding = 'async';
    liveLink.appendChild(liveIcon);

    const instagramLink = document.createElement('a');
    instagramLink.href = item.instagramUrl || 'https://www.instagram.com/carveout.official';
    instagramLink.target = '_blank';
    instagramLink.rel = 'noopener';
    instagramLink.setAttribute('aria-label', `${item.name}のInstagram`);

    const instagramIcon = document.createElement('img');
    instagramIcon.src = 'https://ccarveout.jp/wp-content/themes/carveout_2/images/insta_black.png';
    instagramIcon.alt = 'Instagram';
    instagramIcon.loading = 'lazy';
    instagramIcon.decoding = 'async';
    instagramLink.appendChild(instagramIcon);

    actions.append(liveLink, instagramLink);
    body.append(category, name, actions);
    card.append(image, body);

    return card;
  };

  const liverTrack = document.getElementById('featuredLiverTrack');
  const liverGrid = document.getElementById('allLiverGrid');
  const liverCategoryTabs = document.getElementById('liverCategoryTabs');
  const liverItems = window.carveout17LiveLivers || [];

  if (liverTrack && Array.isArray(liverItems)) {
    const featuredItems = liverItems.slice(0, 6);
    const fragment = document.createDocumentFragment();

    featuredItems.forEach((item) => {
      fragment.appendChild(createLiverCard(item));
    });

    liverTrack.replaceChildren(fragment);
  }

  if (liverGrid && Array.isArray(liverItems)) {
    const renderLiverGrid = (category = 'ALL') => {
      const fragment = document.createDocumentFragment();
      const filteredItems = category === 'ALL'
        ? liverItems
        : liverItems.filter((item) => item.category === category);

      filteredItems.forEach((item) => {
        fragment.appendChild(createLiverCard(item));
      });

      liverGrid.replaceChildren(fragment);
    };

    if (liverCategoryTabs) {
      const categories = ['ALL', ...new Set(liverItems.map((item) => item.category).filter(Boolean))];
      const tabsFragment = document.createDocumentFragment();

      categories.forEach((category, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `news-tab liver-tab${index === 0 ? ' is-active' : ''}`;
        button.textContent = category;
        button.setAttribute('aria-pressed', index === 0 ? 'true' : 'false');

        button.addEventListener('click', () => {
          liverCategoryTabs.querySelectorAll('.liver-tab').forEach((tab) => {
            tab.classList.remove('is-active');
            tab.setAttribute('aria-pressed', 'false');
          });
          button.classList.add('is-active');
          button.setAttribute('aria-pressed', 'true');
          renderLiverGrid(category);
        });

        tabsFragment.appendChild(button);
      });

      liverCategoryTabs.replaceChildren(tabsFragment);
    }

    renderLiverGrid();
  }

  const initMobileScrollbars = () => {
    const scrollers = [
      document.querySelector('#event .event-feature-wrap'),
      document.querySelector('#news .news-carousel'),
      document.querySelector('#livers .featured-liver-carousel')
    ].filter(Boolean);

    if (!scrollers.length) {
      return;
    }

    const setBar = (scroller) => {
      let bar = scroller.nextElementSibling;

      if (!bar || !bar.classList.contains('mobile-progressbar')) {
        bar = document.createElement('div');
        bar.className = 'mobile-progressbar';
        bar.setAttribute('aria-hidden', 'true');

        const thumb = document.createElement('span');
        bar.appendChild(thumb);
        scroller.insertAdjacentElement('afterend', bar);
      }

      return bar;
    };

    const getTargetMaxScroll = (scroller) => {
      const maxNativeScroll = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
      const cards = [...scroller.querySelectorAll('.news-card, .featured-liver-card')]
        .filter((card) => window.getComputedStyle(card).display !== 'none');
      const lastCard = cards[cards.length - 1];

      if (!lastCard) {
        return maxNativeScroll;
      }

      const targetScroll = lastCard.offsetLeft + lastCard.offsetWidth - scroller.clientWidth;
      return Math.max(0, Math.min(maxNativeScroll, targetScroll));
    };

    const updateScroller = (scroller) => {
      const bar = setBar(scroller);
      const thumb = bar.querySelector('span');
      const maxScroll = getTargetMaxScroll(scroller);

      if (!thumb || maxScroll <= 1) {
        bar.classList.add('is-scrollbar-hidden');
        return;
      }

      bar.classList.remove('is-scrollbar-hidden');

      const progress = Math.min(1, Math.max(0, scroller.scrollLeft / maxScroll));
      const visibleRatio = Math.min(1, scroller.clientWidth / (maxScroll + scroller.clientWidth));
      const thumbWidth = Math.max(86, Math.round(bar.clientWidth * Math.max(0.34, visibleRatio)));
      const thumbLeft = Math.round((bar.clientWidth - thumbWidth) * progress);

      thumb.style.width = `${thumbWidth}px`;
      thumb.style.transform = `translate3d(${thumbLeft}px, 0, 0)`;
    };

    const scheduleUpdate = (scroller) => {
      if (scroller.dataset.scrollbarTicking === 'true') {
        return;
      }

      scroller.dataset.scrollbarTicking = 'true';

      window.requestAnimationFrame(() => {
        scroller.dataset.scrollbarTicking = 'false';
        updateScroller(scroller);
      });
    };

    scrollers.forEach((scroller) => {
      setBar(scroller);
      updateScroller(scroller);
      scroller.addEventListener('scroll', () => scheduleUpdate(scroller), { passive: true });
      scroller.addEventListener('touchmove', () => scheduleUpdate(scroller), { passive: true });
    });

    const updateAll = () => scrollers.forEach(updateScroller);
    const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(updateAll) : null;

    if (resizeObserver) {
      scrollers.forEach((scroller) => resizeObserver.observe(scroller));
    }

    window.addEventListener('resize', updateAll, { passive: true });
    window.addEventListener('orientationchange', updateAll, { passive: true });
    window.addEventListener('load', updateAll, { once: true });

    [120, 420, 900, 1600].forEach((delay) => window.setTimeout(updateAll, delay));
  };

  initMobileScrollbars();

  const contactForm = document.getElementById('contactForm');

  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const message = document.getElementById('message').value;

      if (!name || !email || !message) {
        alert('必須項目を入力してください。');
        return;
      }

      alert('お問い合わせを送信しました。ありがとうございました。');
      contactForm.reset();
    });
  }
});
