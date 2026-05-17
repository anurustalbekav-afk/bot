// fear.dev — i18n. Three locales: ru (default), uk, en.
window.FD_I18N = (function () {
  const dict = {
    ru: {
      'meta.title.login': 'fear.dev — Вход',
      'meta.title.register': 'fear.dev — Регистрация',
      'meta.title.dashboard': 'fear.dev — Панель',
      'brand.name': 'FEAR.DEV',
      'login.subtitle': 'Доступ только для зарегистрированных разработчиков и клиентов.',
      'register.subtitle': 'Создайте аккаунт fear.dev, чтобы покупать моды SAMP и скрипты.',
      'placeholder.identifier': 'Email или Логин',
      'placeholder.email': 'Email',
      'placeholder.login': 'Логин',
      'placeholder.password': 'Пароль',
      'placeholder.password_confirm': 'Повторите пароль',
      'btn.login': 'Войти',
      'btn.register': 'Создать аккаунт',
      'btn.logout': 'Выйти',
      'alt.toRegister': 'Нет аккаунта?',
      'alt.toRegister.link': 'Регистрация',
      'alt.toLogin': 'Уже есть аккаунт?',
      'alt.toLogin.link': 'Войти',
      'meta.system': 'Система защищена',
      'meta.developer': 'fear.dev · build 0.2-php',
      'dash.welcome': 'Добро пожаловать,',
      'dash.subtitle': 'Это ваш кабинет fear.dev. Скоро здесь появится каталог модов и скриптов.',
      'dash.email': 'Email',
      'dash.login': 'Логин',
      'dash.id': 'ID',
      'dash.role': 'Роль',
      'dash.created': 'Регистрация',
      'btn.admin': 'Админка',
      'meta.title.admin': 'fear.dev — Админка',
      'admin.title': 'Админка',
      'admin.subtitle': 'Управление пользователями и ролями.',
      'admin.toDashboard': 'В кабинет',
      'admin.search': 'Поиск по логину или email',
      'admin.col.login': 'Логин',
      'admin.col.email': 'Email',
      'admin.col.role': 'Роль',
      'admin.col.created': 'Регистрация',
      'admin.delete': 'Удалить',
      'admin.confirm_delete': 'Удалить пользователя?',
      'admin.empty': 'Пользователи не найдены.',
      'admin.total': 'Всего:',
      'admin.you': 'вы',
      'err.email_required': 'Укажите email.',
      'err.invalid_email': 'Некорректный email.',
      'err.email_too_long': 'Слишком длинный email.',
      'err.login_required': 'Укажите логин.',
      'err.invalid_login': 'Логин: 3–24 символа, латиница/цифры/_.',
      'err.invalid_password': 'Некорректный пароль.',
      'err.password_too_short': 'Пароль должен быть не короче 8 символов.',
      'err.password_too_long': 'Пароль слишком длинный.',
      'err.password_mismatch': 'Пароли не совпадают.',
      'err.email_taken': 'Этот email уже зарегистрирован.',
      'err.login_taken': 'Этот логин уже занят.',
      'err.invalid_credentials': 'Неверный логин или пароль.',
      'err.missing_credentials': 'Введите логин/email и пароль.',
      'err.rate_limited': 'Слишком много попыток. Попробуйте позже.',
      'err.network': 'Ошибка сети. Попробуйте ещё раз.',
      'err.unknown': 'Что-то пошло не так.',
      'err.forbidden': 'Доступ запрещён.',
      'err.unauthorized': 'Нужна авторизация.',
      'err.user_not_found': 'Пользователь не найден.',
      'err.invalid_request': 'Некорректный запрос.',
      'err.cannot_demote_self': 'Нельзя снять админку с себя через веб.',
      'err.cannot_delete_self': 'Нельзя удалить самого себя.',
      'err.cannot_delete_last_admin': 'Это последний админ — удаление запрещено.',
      'ok.registered': 'Аккаунт создан. Перенаправляем…',
      'ok.logged_in': 'Вход выполнен. Перенаправляем…',
      'ok.role_updated': 'Роль обновлена.',
      'ok.user_deleted': 'Пользователь удалён.',
    },
    uk: {
      'meta.title.login': 'fear.dev — Вхід',
      'meta.title.register': 'fear.dev — Реєстрація',
      'meta.title.dashboard': 'fear.dev — Панель',
      'brand.name': 'FEAR.DEV',
      'login.subtitle': 'Доступ лише для зареєстрованих розробників і клієнтів.',
      'register.subtitle': 'Створіть акаунт fear.dev, щоб купувати моди SAMP і скрипти.',
      'placeholder.identifier': 'Email або Логін',
      'placeholder.email': 'Email',
      'placeholder.login': 'Логін',
      'placeholder.password': 'Пароль',
      'placeholder.password_confirm': 'Повторіть пароль',
      'btn.login': 'Увійти',
      'btn.register': 'Створити акаунт',
      'btn.logout': 'Вийти',
      'alt.toRegister': 'Немає акаунта?',
      'alt.toRegister.link': 'Реєстрація',
      'alt.toLogin': 'Уже маєте акаунт?',
      'alt.toLogin.link': 'Увійти',
      'meta.system': 'Систему захищено',
      'meta.developer': 'fear.dev · build 0.2-php',
      'dash.welcome': 'Вітаємо,',
      'dash.subtitle': 'Це ваш кабінет fear.dev. Незабаром тут з\u2019явиться каталог модів і скриптів.',
      'dash.email': 'Email',
      'dash.login': 'Логін',
      'dash.id': 'ID',
      'dash.role': 'Роль',
      'dash.created': 'Реєстрація',
      'btn.admin': 'Адмінка',
      'meta.title.admin': 'fear.dev — Адмінка',
      'admin.title': 'Адмінка',
      'admin.subtitle': 'Керування користувачами та ролями.',
      'admin.toDashboard': 'До кабінету',
      'admin.search': 'Пошук за логіном або email',
      'admin.col.login': 'Логін',
      'admin.col.email': 'Email',
      'admin.col.role': 'Роль',
      'admin.col.created': 'Реєстрація',
      'admin.delete': 'Видалити',
      'admin.confirm_delete': 'Видалити користувача?',
      'admin.empty': 'Користувачів не знайдено.',
      'admin.total': 'Усього:',
      'admin.you': 'ви',
      'err.email_required': 'Вкажіть email.',
      'err.invalid_email': 'Некоректний email.',
      'err.email_too_long': 'Завеликий email.',
      'err.login_required': 'Вкажіть логін.',
      'err.invalid_login': 'Логін: 3–24 символи, латиниця/цифри/_.',
      'err.invalid_password': 'Некоректний пароль.',
      'err.password_too_short': 'Пароль має містити щонайменше 8 символів.',
      'err.password_too_long': 'Пароль завеликий.',
      'err.password_mismatch': 'Паролі не збігаються.',
      'err.email_taken': 'Цей email уже зареєстровано.',
      'err.login_taken': 'Цей логін уже зайнято.',
      'err.invalid_credentials': 'Невірний логін або пароль.',
      'err.missing_credentials': 'Введіть логін/email і пароль.',
      'err.rate_limited': 'Забагато спроб. Спробуйте пізніше.',
      'err.network': 'Помилка мережі. Спробуйте ще раз.',
      'err.unknown': 'Щось пішло не так.',
      'err.forbidden': 'Доступ заборонено.',
      'err.unauthorized': 'Потрібна авторизація.',
      'err.user_not_found': 'Користувача не знайдено.',
      'err.invalid_request': 'Некоректний запит.',
      'err.cannot_demote_self': 'Не можна зняти адмінку із себе через веб.',
      'err.cannot_delete_self': 'Не можна видалити самого себе.',
      'err.cannot_delete_last_admin': 'Це останній адмін — видалення заборонено.',
      'ok.registered': 'Акаунт створено. Переадресація…',
      'ok.logged_in': 'Вхід виконано. Переадресація…',
      'ok.role_updated': 'Роль оновлено.',
      'ok.user_deleted': 'Користувача видалено.',
    },
    en: {
      'meta.title.login': 'fear.dev — Sign in',
      'meta.title.register': 'fear.dev — Create account',
      'meta.title.dashboard': 'fear.dev — Dashboard',
      'brand.name': 'FEAR.DEV',
      'login.subtitle': 'Access is granted to registered developers and clients only.',
      'register.subtitle': 'Create a fear.dev account to buy SAMP mods and scripts.',
      'placeholder.identifier': 'Email or Username',
      'placeholder.email': 'Email',
      'placeholder.login': 'Username',
      'placeholder.password': 'Password',
      'placeholder.password_confirm': 'Confirm password',
      'btn.login': 'Sign in',
      'btn.register': 'Create account',
      'btn.logout': 'Sign out',
      'alt.toRegister': "Don't have an account?",
      'alt.toRegister.link': 'Register',
      'alt.toLogin': 'Already have an account?',
      'alt.toLogin.link': 'Sign in',
      'meta.system': 'Secure session',
      'meta.developer': 'fear.dev · build 0.2-php',
      'dash.welcome': 'Welcome,',
      'dash.subtitle': 'This is your fear.dev account. Mods and scripts catalog is coming soon.',
      'dash.email': 'Email',
      'dash.login': 'Username',
      'dash.id': 'ID',
      'dash.role': 'Role',
      'dash.created': 'Joined',
      'btn.admin': 'Admin',
      'meta.title.admin': 'fear.dev — Admin',
      'admin.title': 'Admin panel',
      'admin.subtitle': 'Manage users and roles.',
      'admin.toDashboard': 'Back to dashboard',
      'admin.search': 'Search by username or email',
      'admin.col.login': 'Username',
      'admin.col.email': 'Email',
      'admin.col.role': 'Role',
      'admin.col.created': 'Joined',
      'admin.delete': 'Delete',
      'admin.confirm_delete': 'Delete user?',
      'admin.empty': 'No users found.',
      'admin.total': 'Total:',
      'admin.you': 'you',
      'err.email_required': 'Email is required.',
      'err.invalid_email': 'Invalid email.',
      'err.email_too_long': 'Email is too long.',
      'err.login_required': 'Username is required.',
      'err.invalid_login': 'Username: 3–24 chars, letters/digits/_.',
      'err.invalid_password': 'Invalid password.',
      'err.password_too_short': 'Password must be at least 8 characters.',
      'err.password_too_long': 'Password is too long.',
      'err.password_mismatch': 'Passwords do not match.',
      'err.email_taken': 'This email is already registered.',
      'err.login_taken': 'This username is taken.',
      'err.invalid_credentials': 'Invalid credentials.',
      'err.missing_credentials': 'Please enter your username/email and password.',
      'err.rate_limited': 'Too many attempts. Please try again later.',
      'err.network': 'Network error. Please try again.',
      'err.unknown': 'Something went wrong.',
      'err.forbidden': 'Forbidden.',
      'err.unauthorized': 'Authentication required.',
      'err.user_not_found': 'User not found.',
      'err.invalid_request': 'Invalid request.',
      'err.cannot_demote_self': 'You cannot demote yourself via the web.',
      'err.cannot_delete_self': 'You cannot delete yourself.',
      'err.cannot_delete_last_admin': 'This is the last admin — deletion blocked.',
      'ok.registered': 'Account created. Redirecting…',
      'ok.logged_in': 'Signed in. Redirecting…',
      'ok.role_updated': 'Role updated.',
      'ok.user_deleted': 'User deleted.',
    },
  };

  const SUPPORTED = ['ru', 'uk', 'en'];

  function getLocale() {
    const stored = localStorage.getItem('fd_lang');
    if (stored && SUPPORTED.includes(stored)) return stored;
    const nav = (navigator.language || 'ru').slice(0, 2).toLowerCase();
    return SUPPORTED.includes(nav) ? nav : 'ru';
  }

  function setLocale(loc) {
    if (!SUPPORTED.includes(loc)) return;
    localStorage.setItem('fd_lang', loc);
    apply();
  }

  function t(key) {
    const loc = getLocale();
    return (dict[loc] && dict[loc][key]) || (dict.ru[key]) || key;
  }

  function apply() {
    document.documentElement.lang = getLocale();
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      el.textContent = t(el.getAttribute('data-i18n'));
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      el.setAttribute('placeholder', t(el.getAttribute('data-i18n-placeholder')));
    });
    document.querySelectorAll('[data-i18n-title]').forEach((el) => {
      const key = el.getAttribute('data-i18n-title');
      document.title = t(key);
    });
    document.querySelectorAll('[data-lang-btn]').forEach((b) => {
      b.classList.toggle('active', b.getAttribute('data-lang-btn') === getLocale());
    });
  }

  function mount() {
    document.querySelectorAll('[data-lang-btn]').forEach((b) => {
      b.addEventListener('click', () => setLocale(b.getAttribute('data-lang-btn')));
    });
    apply();
  }

  return { t, apply, mount, getLocale, setLocale, SUPPORTED };
})();
