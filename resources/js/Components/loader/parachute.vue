<script setup>
    defineProps({
        msg: {
            type: String,
            default: 'Loading...'
        }
    });
</script>
<template>
    <!-- From Uiverse.io by toniflutter -->
    <div class="parachute-loader">
        <div class="wind-line wind-1"></div>
        <div class="wind-line wind-2"></div>
        <div class="wind-line wind-3"></div>
        <div class="wind-line wind-4"></div>
        <div class="wind-line wind-5"></div>

        <div class="cloud cloud-1"></div>
        <div class="cloud cloud-2"></div>

        <div class="parachutist-wrapper">
            <svg
                class="parachute-svg"
                viewBox="0 0 100 120"
                xmlns="http://www.w3.org/2000/svg"
                >
                <line
                    x1="10"
                    y1="40"
                    x2="45"
                    y2="80"
                    stroke="#eee"
                    stroke-width="2"
                ></line>
                <line
                    x1="90"
                    y1="40"
                    x2="55"
                    y2="80"
                    stroke="#eee"
                    stroke-width="2"
                ></line>
                <line
                    x1="50"
                    y1="35"
                    x2="50"
                    y2="80"
                    stroke="#eee"
                    stroke-width="2"
                ></line>

                <path d="M 10 40 Q 50 -10 90 40 Z" fill="#FF6B6B"></path>
                <path
                    d="M 30 28 Q 50 0 70 28 L 50 35 Z"
                    fill="#FFFFFF"
                    opacity="0.3"
                ></path>

                <circle cx="50" cy="85" r="8" fill="#FFFFFF"></circle>
                <rect x="42" y="93" width="16" height="20" rx="5" fill="#1E90FF"></rect>
                <path
                    d="M 42 95 L 30 85"
                    stroke="#000000"
                    stroke-width="3"
                    stroke-linecap="round"
                ></path>
                <path
                    d="M 58 95 L 70 85"
                    stroke="#000000"
                    stroke-width="3"
                    stroke-linecap="round"
                ></path>
                <path
                    d="M 45 113 L 40 120"
                    stroke="#000000"
                    stroke-width="3"
                    stroke-linecap="round"
                ></path>
                <path
                    d="M 55 113 L 60 120"
                    stroke="#000000"
                    stroke-width="3"
                    stroke-linecap="round"
                ></path>
            </svg>
        </div>

        <div class="loading-text">{{ msg }}</div>
    </div>

</template>
<style lang="css" scoped>
    /* From Uiverse.io by toniflutter */
    .parachute-loader {
        width: 300px;
        height: 300px;
        position: relative;
        overflow: hidden;
        background-color: #504d4d;
        border-radius: 12px;
        font-family: sans-serif;
        margin: auto;
    }

    .parachute-loader .wind-line {
        position: absolute;
        width: 2px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 2px;
        bottom: -50px;
        animation: windUp 0.8s linear infinite;
    }

    .parachute-loader .wind-1 {
        left: 20%;
        height: 40px;
        animation-delay: 0s;
        animation-duration: 0.5s;
    }
    .parachute-loader .wind-2 {
        left: 80%;
        height: 60px;
        animation-delay: 0.2s;
        animation-duration: 0.7s;
    }
    .parachute-loader .wind-3 {
        left: 10%;
        height: 30px;
        animation-delay: 0.4s;
        animation-duration: 0.6s;
    }
    .parachute-loader .wind-4 {
        left: 90%;
        height: 50px;
        animation-delay: 0.1s;
        animation-duration: 0.8s;
    }
    .parachute-loader .wind-5 {
        left: 50%;
        height: 25px;
        animation-delay: 0.5s;
        animation-duration: 0.4s;
    }

    .parachute-loader .cloud {
        position: absolute;
        background: #fff;
        border-radius: 50%;
        bottom: -100px;
        opacity: 0.8;
        animation: moveCloudsUp 3s linear infinite;
    }

    .parachute-loader .cloud::after,
    .parachute-loader .cloud::before {
        content: "";
        position: absolute;
        background: inherit;
        border-radius: 50%;
    }

    .parachute-loader .cloud-1 {
        width: 60px;
        height: 60px;
        left: 10%;
        animation-duration: 2.5s;
    }
    .parachute-loader .cloud-1::after {
        width: 40px;
        height: 40px;
        top: -20px;
        left: 10px;
    }
    .parachute-loader .cloud-1::before {
        width: 50px;
        height: 50px;
        top: -10px;
        left: 30px;
    }

    .parachute-loader .cloud-2 {
        width: 80px;
        height: 80px;
        right: 15%;
        animation-duration: 3.2s;
        animation-delay: 1s;
        opacity: 0.6;
    }
    .parachute-loader .cloud-2::after {
        width: 50px;
        height: 50px;
        top: -25px;
        left: 15px;
    }
    .parachute-loader .cloud-2::before {
        width: 60px;
        height: 60px;
        top: -15px;
        left: 40px;
    }

    .parachute-loader .parachutist-wrapper {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100px;
        height: 120px;
        animation: sway 3s ease-in-out infinite;
        z-index: 10;
    }

    .parachute-loader .parachute-svg {
        width: 100%;
        height: 100%;
        display: block;
        filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.1));
    }

    .parachute-loader .loading-text {
        position: absolute;
        bottom: 20px;
        width: 100%;
        text-align: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
        letter-spacing: 2px;
        text-transform: uppercase;
        animation: pulse 1.5s infinite;
        z-index: 20;
    }

    @keyframes sway {
        0%,
        100% {
            transform: translate(-50%, -50%) rotate(-5deg);
        }
        50% {
            transform: translate(-50%, -45%) rotate(5deg);
        }
    }

    @keyframes moveCloudsUp {
        0% {
            bottom: -100px;
            opacity: 0;
        }
        20% {
            opacity: 0.9;
        }
        80% {
            opacity: 0.9;
        }
        100% {
            bottom: 350px;
            opacity: 0;
        }
    }

    @keyframes windUp {
        0% {
            bottom: -60px;
            opacity: 0;
        }
        50% {
            opacity: 1;
        }
        100% {
            bottom: 350px;
            opacity: 0;
        }
    }

    @keyframes pulse {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

</style>
