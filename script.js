/* ============================================================
   ACLIVE — interações da landing page
   ============================================================ */

// Ano atual no rodapé
document.getElementById('year').textContent = new Date().getFullYear();

// Navbar muda de fundo ao rolar
const navbar = document.getElementById('navbar');
const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 30);
onScroll();
window.addEventListener('scroll', onScroll, { passive: true });

// Menu mobile
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('navLinks');

hamburger.addEventListener('click', () => {
  const open = navLinks.classList.toggle('open');
  hamburger.classList.toggle('open', open);
  hamburger.setAttribute('aria-expanded', String(open));
  document.body.style.overflow = open ? 'hidden' : '';
});

navLinks.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    hamburger.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  });
});

// Animação de entrada (reveal) com stagger
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    });
  },
  { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
);

document.querySelectorAll('.reveal').forEach((el, i) => {
  el.style.transitionDelay = `${(i % 4) * 80}ms`;
  revealObserver.observe(el);
});

// Contadores animados na faixa de números
const animateCount = (el) => {
  const target = parseFloat(el.dataset.count);
  const prefix = el.dataset.prefix || '';
  const suffix = el.dataset.suffix || '';
  const duration = 1600;
  const start = performance.now();

  const tick = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = prefix + Math.round(target * eased) + suffix;
    if (progress < 1) requestAnimationFrame(tick);
  };

  requestAnimationFrame(tick);
};

const statsObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      animateCount(entry.target);
      statsObserver.unobserve(entry.target);
    });
  },
  { threshold: 0.6 }
);

document.querySelectorAll('.stat-num[data-count]').forEach((el) => statsObserver.observe(el));

// Serviços: acordeão — um aberto por vez
const services = document.querySelectorAll('.svc');

services.forEach((svc) => {
  svc.querySelector('.svc-head').addEventListener('click', () => {
    const willOpen = !svc.classList.contains('open');
    services.forEach((other) => {
      other.classList.remove('open');
      other.querySelector('.svc-head').setAttribute('aria-expanded', 'false');
    });
    if (willOpen) {
      svc.classList.add('open');
      svc.querySelector('.svc-head').setAttribute('aria-expanded', 'true');
    }
  });
});

// Depoimentos: slider com setas, bolinhas e autoplay
const quotes = document.querySelectorAll('.quote');
const dotsWrap = document.getElementById('quoteDots');
let quoteIndex = 0;
let quoteTimer;

quotes.forEach((_, i) => {
  const dot = document.createElement('button');
  dot.className = 'quote-dot' + (i === 0 ? ' active' : '');
  dot.setAttribute('aria-label', `Depoimento ${i + 1}`);
  dot.addEventListener('click', () => showQuote(i));
  dotsWrap.appendChild(dot);
});

const showQuote = (i, fromUser = true) => {
  quoteIndex = (i + quotes.length) % quotes.length;
  quotes.forEach((q, k) => q.classList.toggle('active', k === quoteIndex));
  dotsWrap.querySelectorAll('.quote-dot').forEach((d, k) => d.classList.toggle('active', k === quoteIndex));
  if (fromUser) restartAutoplay();
};

const restartAutoplay = () => {
  clearInterval(quoteTimer);
  quoteTimer = setInterval(() => showQuote(quoteIndex + 1, false), 6000);
};

document.getElementById('quotePrev').addEventListener('click', () => showQuote(quoteIndex - 1));
document.getElementById('quoteNext').addEventListener('click', () => showQuote(quoteIndex + 1));
restartAutoplay();

// Formulário de contato: envia para contato.php sem recarregar a página
const leadForm = document.getElementById('leadForm');
const formStatus = document.getElementById('formStatus');

leadForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!leadForm.checkValidity()) {
    leadForm.reportValidity();
    return;
  }

  const submitBtn = leadForm.querySelector('.btn-submit');
  submitBtn.disabled = true;
  submitBtn.style.opacity = '0.7';
  formStatus.className = 'form-status';
  formStatus.textContent = 'Enviando...';

  try {
    const response = await fetch(leadForm.action, {
      method: 'POST',
      body: new FormData(leadForm),
      headers: { Accept: 'application/json' },
    });
    const data = await response.json();

    if (data.ok) {
      formStatus.className = 'form-status ok';
      formStatus.textContent = 'Recebido! Nossa equipe entra em contato em breve. 🚀';
      leadForm.reset();
    } else {
      throw new Error(data.erro || 'Falha no envio');
    }
  } catch (err) {
    formStatus.className = 'form-status err';
    formStatus.textContent = 'Não foi possível enviar agora. Chame a gente no WhatsApp!';
  } finally {
    submitBtn.disabled = false;
    submitBtn.style.opacity = '';
  }
});
