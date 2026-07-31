<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/discussions.php';

$user = refresh_authenticated_user(require_login());
auth_no_store();

if ((string) ($user['role'] ?? '') !== 'customer') {
    header('Location: ' . user_home($user));
    exit;
}

$requestedNext = safe_next_path((string) ($_POST['next'] ?? $_GET['next'] ?? ($_SESSION['profile_return'] ?? 'account.php')));
$profile = customer_profile($user);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (isset($_POST['later'])) {
        unset($_SESSION['profile_setup_pending'], $_SESSION['profile_return']);
        header('Location: ' . $requestedNext);
        exit;
    } else {
        $result = update_customer_profile(
            $user,
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['bio'] ?? ''),
            (string) ($_POST['avatar_style'] ?? 'rose'),
            isset($_FILES['profile_photo']) && is_array($_FILES['profile_photo']) ? $_FILES['profile_photo'] : null,
            !empty($_POST['remove_photo'])
        );
        if ($result['ok']) {
            $_SESSION['account_notice'] = (string) $result['message'];
            unset($_SESSION['profile_setup_pending'], $_SESSION['profile_return']);
            header('Location: ' . $requestedNext);
            exit;
        }
        $error = (string) $result['message'];
        $profile['name'] = (string) ($_POST['name'] ?? $profile['name']);
        $profile['bio'] = (string) ($_POST['bio'] ?? $profile['bio']);
        $profile['avatar_style'] = (string) ($_POST['avatar_style'] ?? $profile['avatar_style']);
    }
}

$isWelcome = !empty($_SESSION['profile_setup_pending']);
$avatarUrl = profile_avatar_url($profile);
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Set Up Profile | EcoCart</title>
    <link href="public/output-public.css?v=<?= htmlspecialchars((string) @filemtime(__DIR__ . '/public/output-public.css')) ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="data:,">
</head>
<body class="min-h-screen bg-[#eef0f4] text-slate-950">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex min-h-[70px] w-[min(1180px,calc(100%_-_32px))] items-center gap-3">
            <a class="flex items-center gap-2" href="index.php"><span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="leaf" class="h-5 w-5"></i></span><span class="text-2xl font-black">Eco<span class="text-rose-600">Cart.</span></span></a>
            <div class="ml-auto flex items-center gap-2 text-xs font-bold text-slate-500"><i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i> Private profile editor</div>
        </div>
    </header>

    <main class="mx-auto w-[min(1180px,calc(100%_-_32px))] py-5 sm:py-7">
        <div class="mb-5 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg <?= $isWelcome ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>"><i data-lucide="<?= $isWelcome ? 'badge-check' : 'user-round-cog' ?>" class="h-5 w-5"></i></span>
                <div><p class="font-black"><?= $isWelcome ? 'Your account is ready' : 'Profile settings' ?></p><p class="text-xs text-slate-500"><?= $isWelcome ? 'Add a photo and a little personality before you continue.' : 'Changes appear beside your product discussions.' ?></p></div>
            </div>
            <div class="flex items-center gap-2 text-[10px] font-black uppercase text-slate-400"><span class="h-1.5 w-12 rounded-full bg-rose-600"></span> Identity setup</div>
        </div>

        <section class="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl lg:grid-cols-[380px_minmax(0,1fr)]">
            <aside class="relative overflow-hidden bg-slate-950 p-6 text-white sm:p-8">
                <div class="absolute inset-x-0 top-0 h-28 bg-rose-600"></div>
                <div class="relative pt-10">
                    <div class="relative h-28 w-28 overflow-hidden rounded-lg border-4 border-slate-950 shadow-xl <?= htmlspecialchars(avatar_class((string) $profile['avatar_style'])) ?>">
                        <img class="<?= $avatarUrl ? '' : 'hidden' ?> h-full w-full object-cover" src="<?= htmlspecialchars((string) ($avatarUrl ?? '')) ?>" alt="Profile preview" data-profile-image>
                        <span class="<?= $avatarUrl ? 'hidden' : 'grid' ?> h-full w-full place-items-center text-4xl font-black" data-profile-fallback><?= htmlspecialchars(profile_initial((string) $profile['name'])) ?></span>
                    </div>
                    <p class="mt-5 text-[10px] font-black uppercase text-rose-300">EcoCart member</p>
                    <h1 class="mt-1 break-words text-3xl font-black leading-tight" data-profile-name><?= htmlspecialchars((string) $profile['name']) ?></h1>
                    <p class="mt-2 min-h-10 text-sm leading-5 text-slate-400" data-profile-bio><?= trim((string) $profile['bio']) !== '' ? htmlspecialchars((string) $profile['bio']) : 'Add a short note so shoppers know a little about you.' ?></p>

                    <div class="mt-6 grid grid-cols-2 gap-2">
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3"><i data-lucide="badge-check" class="h-4 w-4 text-emerald-300"></i><p class="mt-2 text-xs font-black">Registered</p><p class="mt-0.5 text-[10px] text-slate-500">Customer account</p></div>
                        <div class="rounded-lg border border-white/10 bg-white/5 p-3"><i data-lucide="message-square-text" class="h-4 w-4 text-cyan-300"></i><p class="mt-2 text-xs font-black">Discussion ready</p><p class="mt-0.5 text-[10px] text-slate-500">Questions & reviews</p></div>
                    </div>

                    <div class="mt-6 border-t border-white/10 pt-5">
                        <p class="text-[10px] font-black uppercase text-slate-500">How others see you</p>
                        <div class="mt-3 flex items-start gap-3 rounded-lg bg-white p-4 text-slate-950 shadow-lg">
                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg <?= htmlspecialchars(avatar_class((string) $profile['avatar_style'])) ?>">
                                <img class="<?= $avatarUrl ? '' : 'hidden' ?> h-full w-full object-cover" src="<?= htmlspecialchars((string) ($avatarUrl ?? '')) ?>" alt="" data-profile-image>
                                <span class="<?= $avatarUrl ? 'hidden' : 'grid' ?> h-full w-full place-items-center text-sm font-black" data-profile-fallback><?= htmlspecialchars(profile_initial((string) $profile['name'])) ?></span>
                            </div>
                            <div class="min-w-0"><p class="truncate text-xs font-black" data-profile-name><?= htmlspecialchars((string) $profile['name']) ?></p><div class="mt-1 flex text-amber-400"><?php for ($i = 0; $i < 5; $i++): ?><i data-lucide="star" class="h-3 w-3 fill-current"></i><?php endfor; ?></div><p class="mt-2 text-[11px] leading-4 text-slate-500">Helpful product tip appears here.</p></div>
                        </div>
                    </div>
                </div>
            </aside>

            <form class="p-5 sm:p-8" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($requestedNext) ?>">

                <div class="flex items-start gap-3 border-b border-slate-100 pb-5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-950 text-white"><i data-lucide="contact-round" class="h-5 w-5"></i></span>
                    <div><h2 class="text-xl font-black">Build your customer profile</h2><p class="mt-1 text-xs leading-5 text-slate-500">Use a friendly photo or keep the initials avatar. You can change this later.</p></div>
                </div>

                <?php if ($error): ?><div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm font-bold text-rose-800" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="mt-6 grid gap-5 xl:grid-cols-[220px_1fr]">
                    <div>
                        <p class="text-xs font-black">Profile picture</p>
                        <label class="mt-2 flex min-h-40 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-rose-400 hover:bg-rose-50" data-photo-drop>
                            <span class="grid h-11 w-11 place-items-center rounded-lg bg-white text-rose-600 shadow-sm"><i data-lucide="image-plus" class="h-5 w-5"></i></span>
                            <span class="mt-3 text-xs font-black">Choose a picture</span>
                            <span class="mt-1 text-[10px] leading-4 text-slate-400">JPG, PNG or WebP<br>Maximum 2 MB</span>
                            <input class="sr-only" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" data-photo-input>
                        </label>
                        <?php if ($avatarUrl): ?><label class="mt-2 flex cursor-pointer items-center gap-2 text-xs font-bold text-rose-700"><input class="checkbox checkbox-xs checkbox-error" type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?>
                    </div>

                    <div class="grid content-start gap-5">
                        <label class="block"><span class="mb-1.5 block text-xs font-black">Display name</span><div class="relative"><input class="input input-bordered h-11 min-h-11 w-full rounded-lg bg-white pl-11" name="name" maxlength="120" value="<?= htmlspecialchars((string) $profile['name']) ?>" required data-profile-name-input><i data-lucide="user-round" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i></div></label>

                        <fieldset>
                            <legend class="text-xs font-black">Initials color <span class="font-semibold text-slate-400">(used when no photo is selected)</span></legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <?php foreach (avatar_styles() as $key => $style): ?>
                                    <label class="cursor-pointer" title="<?= htmlspecialchars($style['label']) ?>"><input class="peer sr-only" type="radio" name="avatar_style" value="<?= htmlspecialchars($key) ?>" <?= $profile['avatar_style'] === $key ? 'checked' : '' ?> data-avatar-choice data-avatar-class="<?= htmlspecialchars($style['class']) ?>"><span class="grid h-10 w-10 place-items-center rounded-lg border-2 border-transparent font-black ring-offset-2 transition peer-checked:border-white peer-checked:ring-2 peer-checked:ring-slate-950 <?= htmlspecialchars($style['class']) ?>"><?= htmlspecialchars(profile_initial((string) $profile['name'])) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <label class="block"><span class="mb-1.5 flex items-center justify-between gap-3 text-xs font-black"><span>About you</span><span class="font-semibold text-slate-400"><span data-bio-count><?= strlen((string) $profile['bio']) ?></span>/180</span></span><textarea class="textarea textarea-bordered min-h-24 w-full resize-none rounded-lg bg-white" name="bio" maxlength="180" placeholder="Student, home cook, rider, bargain hunter..." data-profile-bio-input><?= htmlspecialchars((string) $profile['bio']) ?></textarea></label>
                    </div>
                </div>

                <div class="mt-7 flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] leading-5 text-slate-400"><i data-lucide="lock-keyhole" class="mr-1 inline h-3.5 w-3.5"></i>Your email and password are never shown publicly.</p>
                    <div class="flex gap-2"><button class="btn border-slate-200 bg-white" type="submit" name="later" value="1">Later</button><button class="btn border-0 bg-rose-600 text-white hover:bg-rose-700" type="submit"><i data-lucide="check" class="h-4 w-4"></i> <?= $isWelcome ? 'Set up profile now' : 'Save changes' ?></button></div>
                </div>
            </form>
        </section>
    </main>

    <script>
        const nameInput = document.querySelector('[data-profile-name-input]');
        const bioInput = document.querySelector('[data-profile-bio-input]');
        const photoInput = document.querySelector('[data-photo-input]');
        const colorClasses = ['bg-rose-600','bg-cyan-500','bg-emerald-500','bg-amber-400','bg-violet-600','bg-slate-800','text-white','text-slate-950'];
        function refreshTextPreview() {
            const name = nameInput.value.trim() || 'EcoCart customer';
            document.querySelectorAll('[data-profile-name]').forEach((node) => node.textContent = name);
            document.querySelectorAll('[data-profile-fallback]').forEach((node) => node.textContent = name.charAt(0).toUpperCase());
            document.querySelector('[data-profile-bio]').textContent = bioInput.value.trim() || 'Add a short note so shoppers know a little about you.';
            document.querySelector('[data-bio-count]').textContent = bioInput.value.length;
        }
        function refreshColorPreview() {
            const choice = document.querySelector('[data-avatar-choice]:checked');
            document.querySelectorAll('[data-profile-fallback]').forEach((node) => {
                const container = node.parentElement;
                container.classList.remove(...colorClasses);
                (choice?.dataset.avatarClass || 'bg-rose-600 text-white').split(' ').forEach((className) => container.classList.add(className));
            });
        }
        nameInput.addEventListener('input', refreshTextPreview);
        bioInput.addEventListener('input', refreshTextPreview);
        document.querySelectorAll('[data-avatar-choice]').forEach((choice) => choice.addEventListener('change', refreshColorPreview));
        photoInput.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                document.querySelectorAll('[data-profile-image]').forEach((image) => { image.src = String(reader.result); image.classList.remove('hidden'); });
                document.querySelectorAll('[data-profile-fallback]').forEach((node) => { node.classList.add('hidden'); node.classList.remove('grid'); });
            });
            reader.readAsDataURL(file);
        });
        lucide.createIcons();
    </script>
</body>
</html>
