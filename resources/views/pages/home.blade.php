@extends('layouts.webpage')

@section('content')

    <div id="wrapper">

        <div class="float-text show-on-scroll">
            <span><a href="#">Somos Aracode</a></span>
        </div>
        <div class="scrollbar-v show-on-scroll"></div>

        <!-- page preloader begin -->
        {{-- <div id="de-loader"></div> --}}
        <!-- page preloader close -->

        <x-header />

        <x-home-welcome />

        <x-about />

        <x-tape />

        <x-value-proposition />

        {{-- <section class="bg-dark section-dark pt-80 relative jarallax" aria-label="section">
            <img src="images/background/1.webp" class="jarallax-img" alt="">
            <div class="gradient-edge-top"></div>
            <div class="gradient-edge-bottom"></div>
            <div class="sw-overlay op-8"></div>
            <div class="container">
                <div class="row g-4">

                    <div class="col-md-12 wow fadeInUp">
                        <div class="owl-6 no-alpha owl-carousel owl-theme wow mask-right">
                            <img src="images/logo-light/1.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/2.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/3.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/4.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/5.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/6.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/7.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/8.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/9.webp" class="w-100 px-4" alt="">
                            <img src="images/logo-light/10.webp" class="w-100 px-4" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </section> --}}

        {{-- <section id="section-schedule" class="bg-dark section-dark text-light">
            <div class="container">
                <div class="row g-4 gx-5 justify-content-center">
                    <div class="col-lg-6 text-center">
                        <div class="subtitle s2 mb-3 wow fadeInUp" data-wow-delay=".0s">Event Schedule</div>
                        <h2 class="wow fadeInUp" data-wow-delay=".2s">5 Days of AI Excellence</h2>
                    </div>
                </div>
                <div class="row g-4 gx-5 justify-content-center wow fadeInUp">
                    <div class="col-lg-12">
                        <div class="de-tab plain">
                            <ul class="d-tab-nav mb-4 pb-4 d-flex justify-content-between">
                                <li class="active-tab">
                                    <h3>Day 1</h3>
                                    Oct 1, 2025
                                </li>
                                <li>
                                    <h3>Day 2</h3>
                                    Oct 2, 2025
                                </li>
                                <li>
                                    <h3>Day 3</h3>
                                    Oct 3, 2025
                                </li>
                                <li>
                                    <h3>Day 4</h3>
                                    Oct 5, 2025
                                </li>
                                <li>
                                    <h3>Day 5</h3>
                                    Oct 5, 2025
                                </li>
                            </ul>
                            <ul class="d-tab-content pt-3 wow fadeInUp">
                                <!-- day 1 -->
                                <li>
                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                08:00 – 10:00 AM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/1.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Joshua Henry</h5>
                                                        AI Research Lead, DeepTech Labs
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Opening Keynote – The State of AI 2025</h3>
                                                <p class="fs-15 mb-0">Kick off the event with an insightful overview of
                                                    where artificial intelligence is headed. Ava will explore breakthroughs,
                                                    global shifts, and what’s next in deep learning, generative models, and
                                                    AI ethics.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                12:00 – 14:00 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/2.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Leila Zhang</h5>
                                                        VP of Machine Learning, Google
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Building Human-Centered AI Products</h3>
                                                <p class="fs-15 mb-0">This session covers how to design AI solutions that
                                                    prioritize usability, fairness, and real-world impact. Bring your
                                                    laptop—hands-on UX exercises included.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                16:00 – 18:00 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/3.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Carlos Rivera</h5>
                                                        Founder & CEO, NeuralCore
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: AI Policy & Regulation – A Global Overview</h3>
                                                <p class="fs-15 mb-0">Learn how nations and organizations are approaching
                                                    AI governance, including frameworks for data privacy, bias mitigation,
                                                    and accountability in model deployment.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                20:00 – 22:00 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/4.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Maria Gonzalez</h5>
                                                        Founder & CEO, SynthCore AI
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Building a Startup with AI at the Core</h3>
                                                <p class="fs-15 mb-0">Marco shares his journey launching an AI-first
                                                    startup. Discover tips on tech stacks, team-building, funding, and
                                                    scaling responsibly.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->
                                </li>

                                <!-- day 2 -->
                                <li>
                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                09:00 – 10:30 AM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/5.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Leila Zhang</h5>
                                                        Head of AI Strategy, VisionFlow
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Ethical AI — From Theory to Practice</h3>
                                                <p class="fs-15 mb-0">Explore how leading companies are implementing
                                                    fairness, accountability, and transparency in real-world AI systems
                                                    across healthcare and finance.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                11:00 – 12:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/6.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Lisa Zhang</h5>
                                                        AI Ethics Researcher, FairAI Group
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Bias in Data — Hidden Dangers in AI Pipelines</h3>
                                                <p class="fs-15 mb-0">Lisa dives deep into the causes of bias in training
                                                    data and showcases methods to detect and mitigate harm before
                                                    deployment.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                14:00 – 15:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/7.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Markus Blom</h5>
                                                        CTO, SynthMind AI
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Generative Models Beyond Text</h3>
                                                <p class="fs-15 mb-0">A practical tour of the next generation of multimodal
                                                    models generating images, video, and even 3D environments with AI.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                16:00 – 17:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/8.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Priya Natarajan</h5>
                                                        Lead Engineer, CogniWare
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Workshop: Building AI-Powered Interfaces</h3>
                                                <p class="fs-15 mb-0">Learn how to embed conversational AI into web and
                                                    mobile apps using modern open-source frameworks and API-first design.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                </li>

                                <!-- day 3 -->
                                <li>
                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                09:00 – 10:30 AM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/9.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Sofia Romero</h5>
                                                        ML Engineer, NeuronEdge
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Transformers in 2025 — What's Next?</h3>
                                                <p class="fs-15 mb-0">A technical session diving into the future of
                                                    transformer architectures, memory optimization, and scaling challenges.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                11:00 – 12:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/10.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Tomás Eriksson</h5>
                                                        Founder, RealSim AI
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Synthetic Data Generation for Training</h3>
                                                <p class="fs-15 mb-0">Tomás shares tools and techniques for creating
                                                    high-quality synthetic datasets that speed up training and reduce risk.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                14:00 – 15:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/11.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Aisha Mensah</h5>
                                                        Senior AI Strategist, Datavine
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Panel: AI Regulation & Global Policy Outlook</h3>
                                                <p class="fs-15 mb-0">Top voices discuss the global AI policy landscape,
                                                    upcoming legislation, and how it will shape the future of AI deployment.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                16:00 – 17:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/12.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Leo Tanaka</h5>
                                                        Robotics Engineer, MetaForm
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Embodied AI in Robotics</h3>
                                                <p class="fs-15 mb-0">Discover how AI is powering next-gen robotics for
                                                    manufacturing, logistics, and autonomous mobility through real-time
                                                    interaction models.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                </li>

                                <!-- day 4 -->
                                <li>
                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                09:00 – 10:30 AM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/13.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Nina Köhler</h5>
                                                        Chief Product Officer, SynthOS
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: AI in Product Design — From Concept to Launch</h3>
                                                <p class="fs-15 mb-0">Nina shares how AI is revolutionizing product
                                                    development, from ideation to real-time user feedback integration.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                11:00 – 12:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/14.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Emmanuel Ruiz</h5>
                                                        CEO, NextCore Analytics
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Scaling AI Infrastructure for Enterprise</h3>
                                                <p class="fs-15 mb-0">Explore key considerations when deploying and
                                                    managing scalable, secure, and cost-effective AI systems in the
                                                    enterprise space.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                14:00 – 15:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/15.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Isabelle Chen</h5>
                                                        Head of Language Models, LumoAI
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Multilingual AI — Global Challenges & Innovations</h3>
                                                <p class="fs-15 mb-0">How modern LLMs are overcoming linguistic bias,
                                                    translation errors, and dialect diversity in global applications.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                16:00 – 17:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/16.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Connor Walsh</h5>
                                                        Cloud AI Architect, SkyStack
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Workshop: Building AI Pipelines in the Cloud</h3>
                                                <p class="fs-15 mb-0">Hands-on session building a full AI workflow using
                                                    serverless tech, vector databases, and model deployment strategies.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                </li>

                                <!-- day 5 -->
                                <li>
                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                09:00 – 10:30 AM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/17.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Elena Greco</h5>
                                                        Ethics Advisor, Global AI Forum
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Ethical Design in AI — A Human-Centered Approach</h3>
                                                <p class="fs-15 mb-0">A deep dive into responsible AI, highlighting bias
                                                    mitigation, fairness, transparency, and global implications of
                                                    autonomous systems.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                11:00 – 12:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/18.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Marcus Dlamini</h5>
                                                        Founder, EduAI Labs
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Personalized Learning with AI</h3>
                                                <p class="fs-15 mb-0">Explore how AI-driven platforms are transforming
                                                    education with adaptive learning paths and dynamic content delivery.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="border-white-bottom-op-2 pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                14:00 – 15:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/19.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Lara Nguyen</h5>
                                                        GenAI Director, NovaSynth
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Session: Creative AI — From Text to Video</h3>
                                                <p class="fs-15 mb-0">Lara demonstrates how generative AI is transforming
                                                    content creation, with real-time demos in video, audio, and image
                                                    generation.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                    <!-- schedule item begin -->
                                    <div class="pb-5 mb-5">
                                        <div class="row g-4 align-items-center">
                                            <div class="col-md-2">
                                                16:00 – 17:30 PM
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/team/20.webp" class="w-100px rounded-1 me-4"
                                                        alt="">
                                                    <div>
                                                        <h5 class="mb-0">Dr. Hassan Al-Mansour</h5>
                                                        Lead Data Scientist, FutureVision
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h3>Closing Keynote: AI & Humanity — Co-Evolution or Collapse?</h3>
                                                <p class="fs-15 mb-0">A visionary closing on AI’s long-term trajectory,
                                                    human-AI collaboration, and the existential questions we must answer
                                                    now.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- schedule item end -->

                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section> --}}

        <x-products />

        <x-location />
        

        <x-commitment />

        {{-- <section id="section-faq" class="bg-dark section-dark text-light">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="subtitle wow fadeInUp" data-wow-delay=".0s">Everything You Need to Know</div>
                        <h2 class="wow fadeInUp" data-wow-delay=".2s">Frequently Asked Questions</h2>
                    </div>

                    <div class="col-lg-7">
                        <div class="accordion s2 wow fadeInUp">
                            <div class="accordion-section">
                                <div class="accordion-section-title" data-tab="#accordion-a1">
                                    What is the AI Summit 2025?
                                </div>
                                <div class="accordion-section-content" id="accordion-a1">
                                    The AI Summit 2025 is a premier event gathering leading AI experts, thought leaders, and
                                    innovators. It features keynotes, workshops, panels, and networking opportunities
                                    focusing on the latest advancements in artificial intelligence.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a2">
                                    When and where will the event be held?
                                </div>
                                <div class="accordion-section-content" id="accordion-a2">
                                    The AI Summit 2025 will take place from **[Event Dates]** at **[Event Location]**. More
                                    details about the venue and directions will be provided closer to the event.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a3">
                                    How can I register for the event?
                                </div>
                                <div class="accordion-section-content" id="accordion-a3">
                                    You can register for the AI Summit 2025 through our official website. Simply choose your
                                    ticket type and fill out the registration form.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a4">
                                    What ticket options are available?
                                </div>
                                <div class="accordion-section-content" id="accordion-a4">
                                    We offer a range of ticket options, including Standard, VIP, Full Access Pass, Student,
                                    and Virtual tickets. You can find more details about each ticket type on our [Tickets
                                    Page](#).
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a5">
                                    Can I transfer my ticket to someone else?
                                </div>
                                <div class="accordion-section-content" id="accordion-a5">
                                    Tickets are non-transferable. If you are unable to attend, please contact our support
                                    team for assistance.
                                </div>

                                <div class="accordion-section-title" data-tab="#accordion-a6">
                                    Will there be virtual participation?
                                </div>
                                <div class="accordion-section-content" id="accordion-a6">
                                    Yes! For those who can’t attend in person, we offer a **Virtual Ticket**. This provides
                                    access to live-streamed sessions, workshops, and networking opportunities online.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> --}}

        <x-bulletin />

    </div>

    <!-- footer begin -->
    <x-footer />
    <!-- footer end -->

@stop
