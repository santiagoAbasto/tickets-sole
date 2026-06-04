{{-- Full-screen immersive animated backdrop for public pages. Tech / software dev. --}}
<div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-sidebar [perspective:1300px]">
    {{-- Depth gradients --}}
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_14%_12%,rgba(20,184,166,.20),transparent_30%),radial-gradient(circle_at_86%_14%,rgba(79,70,229,.32),transparent_34%),radial-gradient(circle_at_82%_88%,rgba(168,85,247,.20),transparent_30%),linear-gradient(180deg,rgba(11,18,32,.4),#0b1220_92%)]"></div>

    {{-- Subtle grid + flowing aurora --}}
    <div class="support-grid absolute inset-0 opacity-50"></div>
    <div class="aurora absolute inset-0 opacity-30 mix-blend-screen"></div>

    {{-- Tech doodle pattern (very subtle on dark) --}}
    <div class="absolute inset-0 opacity-[0.06]" style="background-image:url('/img/chat-pattern.svg');background-repeat:repeat;background-size:360px 360px;"></div>

    {{-- Floating colour orbs for depth --}}
    <div class="animate-float absolute left-[5%] top-[16%] h-44 w-44 rounded-full bg-brand-500/25 blur-3xl"></div>
    <div class="animate-float-alt absolute right-[7%] top-[52%] h-56 w-56 rounded-full bg-sky-500/20 blur-3xl"></div>
    <div class="animate-float absolute bottom-[8%] left-[42%] h-40 w-40 rounded-full bg-violet-500/15 blur-3xl [animation-delay:-3s]"></div>
    <div class="animate-float-alt absolute left-[24%] top-[60%] h-36 w-36 rounded-full bg-cyan-500/15 blur-3xl [animation-delay:-5s]"></div>
    <div class="animate-float absolute right-[28%] top-[10%] h-32 w-32 rounded-full bg-fuchsia-500/12 blur-3xl [animation-delay:-7s]"></div>

    {{-- Orbiting 3D rings --}}
    <div class="support-ring support-ring-one"></div>
    <div class="support-ring support-ring-two"></div>

    {{-- Floating glass tech nodes — left band --}}
    <div class="dev-node anim-drift-a" style="left:4%;top:14%;"><i data-lucide="code-2" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-c" style="left:9%;top:33%;width:2.1rem;height:2.1rem;animation-delay:-2s;"><i data-lucide="terminal" class="h-3.5 w-3.5"></i></div>
    <div class="dev-node anim-drift-b" style="left:3%;top:55%;width:3.1rem;height:3.1rem;animation-delay:-4s;"><i data-lucide="git-branch" class="h-5 w-5"></i></div>
    <div class="dev-node anim-drift-d" style="left:12%;bottom:20%;animation-delay:-1.5s;"><i data-lucide="database" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-e" style="left:17%;top:22%;width:2.1rem;height:2.1rem;animation-delay:-3s;"><i data-lucide="braces" class="h-3.5 w-3.5"></i></div>
    <div class="dev-node anim-drift-f" style="left:6%;bottom:36%;animation-delay:-5s;"><i data-lucide="cpu" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-a" style="left:21%;bottom:9%;width:2.1rem;height:2.1rem;animation-delay:-6s;"><i data-lucide="package" class="h-3.5 w-3.5"></i></div>

    {{-- Floating glass tech nodes — right band --}}
    <div class="dev-node anim-drift-b" style="right:5%;top:18%;animation-delay:-1s;"><i data-lucide="cloud" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-d" style="right:10%;top:40%;width:3.1rem;height:3.1rem;animation-delay:-3.5s;"><i data-lucide="server" class="h-5 w-5"></i></div>
    <div class="dev-node anim-drift-c" style="right:4%;top:61%;width:2.1rem;height:2.1rem;animation-delay:-2.5s;"><i data-lucide="bug" class="h-3.5 w-3.5"></i></div>
    <div class="dev-node anim-drift-e" style="right:13%;bottom:16%;animation-delay:-4.5s;"><i data-lucide="rocket" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-f" style="right:19%;top:28%;width:2.1rem;height:2.1rem;animation-delay:-6s;"><i data-lucide="git-merge" class="h-3.5 w-3.5"></i></div>
    <div class="dev-node anim-drift-a" style="right:7%;bottom:34%;animation-delay:-2s;"><i data-lucide="wifi" class="h-4 w-4"></i></div>
    <div class="dev-node anim-drift-d" style="right:22%;bottom:11%;width:2.1rem;height:2.1rem;animation-delay:-5.5s;"><i data-lucide="monitor" class="h-3.5 w-3.5"></i></div>
</div>
