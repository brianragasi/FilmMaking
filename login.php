<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

auth_no_store();

$next = safe_next_path((string) ($_POST['next'] ?? $_GET['next'] ?? 'account.php'));
$mode = (string) ($_POST['mode'] ?? $_GET['mode'] ?? 'login');
$mode = $mode === 'register' ? 'register' : 'login';
$errors = [];
$registrationErrorCode = null;
$sessionAuthMessage = consume_auth_login_message();
if ($sessionAuthMessage) {
    $errors[] = $sessionAuthMessage;
}

if ($existingUser = current_user()) {
    header('Location: ' . ($next !== 'account.php' ? $next : user_home($existingUser)));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    } elseif ($mode === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || strlen($name) > 120) {
            $errors[] = 'Enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 10 || strlen($password) > 72) {
            $errors[] = 'Use a password between 10 and 72 characters.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'The passwords do not match.';
        }

        if (!$errors) {
            $result = register_customer($name, $email, $password);
            if ($result['ok']) {
                $_SESSION['account_notice'] = (string) $result['message'];
                $registeredUser = current_user();
                $returnPath = $next !== 'account.php' ? $next : user_home($registeredUser ?? []);
                if (!empty($result['created'])) {
                    $_SESSION['profile_setup_pending'] = true;
                    $_SESSION['profile_return'] = $returnPath;
                    header('Location: profile-setup.php?next=' . rawurlencode($returnPath));
                    exit;
                }
                header('Location: ' . $returnPath);
                exit;
            }
            $registrationErrorCode = (string) ($result['code'] ?? '');
            $errors[] = (string) $result['message'];
        }
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!login_attempt_allowed()) {
            $errors[] = 'Too many sign-in attempts. Try again in 15 minutes.';
        } elseif (attempt_login($email, $password)) {
            $user = current_user();
            header('Location: ' . ($next !== 'account.php' ? $next : user_home($user ?? [])));
            exit;
        } else {
            $loginMessage = consume_auth_login_message();
            $errors[] = $loginMessage ?? (login_attempt_allowed()
                ? 'The email or password is incorrect.'
                : 'Too many sign-in attempts. Try again in 15 minutes.');
        }
    }
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Sign in to your EcoCart account.">
    <title><?= $mode === 'register' ? 'Create Account' : 'Sign In' ?> | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#f6f7f9] text-slate-950">
    <main class="grid min-h-screen lg:grid-cols-[minmax(420px,0.74fr)_minmax(0,1.26fr)]">
        <section class="flex min-h-screen flex-col bg-white px-5 py-4 sm:px-8 lg:px-12">
            <header class="flex items-center justify-between gap-4">
                <a class="flex items-center gap-2" href="index.php" aria-label="EcoCart home">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span>
                    <span class="text-xl font-black sm:text-2xl">Eco<span class="text-rose-600">Cart.</span></span>
                </a>
                <a class="btn btn-square btn-sm border-slate-200 bg-white" href="index.php" aria-label="Return to store" title="Return to store">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </a>
            </header>

            <div class="mx-auto flex w-full max-w-[460px] flex-1 flex-col justify-start pb-8 pt-16 sm:justify-center sm:py-10">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-black uppercase text-rose-600"><?= $mode === 'register' ? 'Join EcoCart' : 'Welcome back' ?></p>
                    <h1 class="mt-1.5 text-3xl font-black leading-tight"><?= $mode === 'register' ? 'Create your account' : 'Sign in to your account' ?></h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500"><?= $mode === 'register' ? 'Keep your details ready for a faster checkout.' : 'Continue shopping with your saved account details.' ?></p>

                    <div class="mt-5 grid grid-cols-2 rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Account access">
                        <a class="btn btn-sm min-h-9 border-0 <?= $mode === 'login' ? 'bg-white text-slate-950 shadow-sm' : 'bg-transparent text-slate-500' ?>" href="login.php?mode=login&amp;next=<?= rawurlencode($next) ?>">Sign in</a>
                        <a class="btn btn-sm min-h-9 border-0 <?= $mode === 'register' ? 'bg-white text-slate-950 shadow-sm' : 'bg-transparent text-slate-500' ?>" href="login.php?mode=register&amp;next=<?= rawurlencode($next) ?>">Create account</a>
                    </div>

                    <?php if ($errors): ?>
                        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900" role="alert">
                            <div class="flex items-start gap-3">
                                <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600"></i>
                                <div>
                                    <?php foreach (array_unique($errors) as $error): ?>
                                        <p><?= htmlspecialchars($error) ?></p>
                                    <?php endforeach; ?>
                                    <?php if ($mode === 'register' && $registrationErrorCode === 'email_exists'): ?>
                                        <a class="mt-2 inline-flex font-black text-rose-700 underline" href="login.php?mode=login&amp;next=<?= rawurlencode($next) ?>">Open the sign-in form</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form class="mt-5 space-y-3" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                        <?php if ($mode === 'register'): ?>
                            <label class="block">
                                <span class="mb-1 block text-xs font-black">Full name</span>
                                <div class="relative">
                                    <input class="peer h-11 min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-12 text-sm text-slate-900 transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/5 focus:outline-none" name="name" autocomplete="name" maxlength="120" value="<?= htmlspecialchars((string) ($_POST['name'] ?? '')) ?>" required>
                                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-slate-400 transition-colors peer-focus:text-rose-600"><i data-lucide="user-round" class="h-[18px] w-[18px]"></i></span>
                                    <span class="pointer-events-none absolute left-11 top-1/2 h-5 w-px -translate-y-1/2 bg-slate-200 transition-colors peer-focus:bg-rose-200"></span>
                                </div>
                            </label>
                        <?php endif; ?>

                        <label class="block">
                            <span class="mb-1 block text-xs font-black">Email address</span>
                            <div class="relative">
                                    <input class="peer h-11 min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-12 text-sm text-slate-900 transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/5 focus:outline-none" type="email" name="email" autocomplete="email" maxlength="160" value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" required>
                                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-slate-400 transition-colors peer-focus:text-rose-600"><i data-lucide="mail" class="h-[18px] w-[18px]"></i></span>
                                    <span class="pointer-events-none absolute left-11 top-1/2 h-5 w-px -translate-y-1/2 bg-slate-200 transition-colors peer-focus:bg-rose-200"></span>
                                </div>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-xs font-black">Password</span>
                            <div class="relative">
                                    <input class="peer h-11 min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-12 text-sm text-slate-900 transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/5 focus:outline-none" type="password" name="password" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>" minlength="10" maxlength="72" required>
                                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-slate-400 transition-colors peer-focus:text-rose-600"><i data-lucide="lock" class="h-[18px] w-[18px]"></i></span>
                                    <span class="pointer-events-none absolute left-11 top-1/2 h-5 w-px -translate-y-1/2 bg-slate-200 transition-colors peer-focus:bg-rose-200"></span>
                                </div>
                        </label>

                        <?php if ($mode === 'register'): ?>
                            <label class="block">
                                <span class="mb-1 block text-xs font-black">Confirm password</span>
                                <div class="relative">
                                    <input class="peer h-11 min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-12 text-sm text-slate-900 transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/5 focus:outline-none" type="password" name="password_confirmation" autocomplete="new-password" minlength="10" maxlength="72" required>
                                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-slate-400 transition-colors peer-focus:text-rose-600"><i data-lucide="shield-check" class="h-[18px] w-[18px]"></i></span>
                                    <span class="pointer-events-none absolute left-11 top-1/2 h-5 w-px -translate-y-1/2 bg-slate-200 transition-colors peer-focus:bg-rose-200"></span>
                                </div>
                            </label>
                        <?php endif; ?>

                        <button class="btn mt-5 min-h-12 w-full border-0 bg-slate-950 text-white hover:bg-rose-600" type="submit">
                            <?= $mode === 'register' ? 'Create account' : 'Sign in' ?>
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </button>
                    </form>

                    <p class="mt-5 text-center text-xs text-slate-500">
                        <?= $mode === 'register' ? 'Already registered?' : 'New to EcoCart?' ?>
                        <a class="font-black text-rose-600 hover:underline" href="login.php?mode=<?= $mode === 'register' ? 'login' : 'register' ?>&amp;next=<?= rawurlencode($next) ?>">
                            <?= $mode === 'register' ? 'Sign in' : 'Create an account' ?>
                        </a>
                    </p>
                </div>
            </div>

            <footer class="text-xs text-slate-400">&copy; <?= date('Y') ?> EcoCart</footer>
        </section>

        <section class="relative hidden min-h-screen overflow-hidden bg-slate-950 lg:block" aria-label="EcoCart sale collection">
            <img class="absolute inset-0 h-full w-full object-cover" src="assets/images/ecocart-share.png" alt="EcoCart sale collection">
            <div class="absolute inset-0 bg-slate-950/18"></div>
            <div class="absolute bottom-10 left-10 max-w-md rounded-lg bg-white p-6 shadow-2xl">
                <p class="text-xs font-black uppercase text-rose-600">Big Blowout Sale</p>
                <p class="mt-2 text-2xl font-black">Everything practical, all in one cart.</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">School, worksite, rider, home, and family essentials at everyday prices.</p>
            </div>
        </section>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>
