<?php
require_once('include/template.inc.php');
$page_title = 'Linux Downloads';
generate_page_header($page_title);
?>
<body>
    <?php
    include(DENG_LIB_DIR.'/topbar.inc.php');
    generate_page_title($page_title);
    ?>
    <div id='content'>
        <div id='page-content'>

<div class="block">
    <article>
        <h1>Stable</h1>
        <p>This is the latest stable version. <?php echo(release_notes_link('ubuntu18-x86_64')); ?></p>
        <?php
        generate_badges('ubuntu18-x86_64', BT_CANDIDATE);
        generate_badges('fedora23-x86_64', BT_CANDIDATE);
        ?>
    </article>
</div>
<div class="block">
    <article>
        <h1>Unstable</h1>
        <p>Unstable builds are made automatically every day when changes are committed to the <a href="source">source repository</a>. They contain work-in-progress code and sometimes may crash on you. Change logs can be found in the <a href="/builds">Autobuilder</a>.</p>
        <?php
        generate_badges('ubuntu18-x86_64', BT_UNSTABLE);
        generate_badges('fedora23-x86_64', BT_UNSTABLE);
        ?>
    </article>
</div>
<div class="block">
    <article>
        <h1>Other Builds</h1>
        <p>Binary packages are available for some Linux distributions. You can also <a href="/manual/devel/compile">compile manually from source</a> (requires CMake 3.1 and Qt 5).</p>

        <h2 id="flatpak">Flatpak</h2>
        <p><a href="http://files.dengine.net/doomsday-unstable.flatpakref">Unstable builds (files.dengine.net/repo)</a></p>
        <p>For more information, see the <a href="https://flatpak.org">flatpak.org website</a>.</p>

        <h2><img src="/images/ubuntu.png" alt="Ubuntu" class="distro-icon">Ubuntu</h2>
        <p><a class="link-external" href="https://launchpad.net/~sjke/+archive/ubuntu/doomsday/">skyjake's PPA</a> has Doomsday builds for a number of versions of Ubuntu. The <a class="link-external" href="http://packages.ubuntu.com/xenial/games/doomsday">Ubuntu repositories</a> should also have stable releases of Doomsday.</p>

        <h2><img src="/images/debian.png" alt="Debian" class="distro-icon">Debian</h2>
        <p>Check Debian's repositories for binary packages.</p>

        <h2><img src="/images/gentoo.png" alt="Gentoo" class="distro-icon">Gentoo</h2>
        <p><a href="https://packages.gentoo.org/packages/games-fps/doomsday" class="link-external">Doomsday Engine package for Gentoo</a></p>

        <h2><img src="/images/opensuse.png" alt="openSUSE" class="distro-icon">openSUSE</h2>
        <p>Binary packages for Doomsday are available in the <a href="https://software.opensuse.org/package/doomsday" class="link-external">openSUSE repositories.</a></p>
    </article>
</div>

        </div>
        <?php generate_sidebar(); ?>
    </div>
    <?php generate_sitemap(); ?>
</body>
