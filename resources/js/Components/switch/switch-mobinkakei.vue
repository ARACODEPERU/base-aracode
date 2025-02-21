<script setup>
    const props = defineProps({
        title: {
            type: String,
            default: 'Activo'
        },
        modelValue: {
            type: Boolean,
            default: false
        },
        icon: {
            type: String,
            default: null
        },
        uId: {
            type: String,
            required: true // Asegúrate de que cada Switch tenga un id único
        }
    });

    const emit = defineEmits(['update:modelValue']);

    function toggle() {
        emit('update:modelValue', !props.modelValue);
    }
</script>

<template>
    <div class="vswitch-holder">
        <div class="vswitch-label">
            <i v-show="icon" class="fa fa-bluetooth-b"></i> <span>{{ title }}</span>
        </div>
        <div class="vswitch-toggle">
            <input 
                type="checkbox" 
                :id="uId" 
                :checked="modelValue" 
                @change="toggle"
            />
            <label :for="uId"></label>
        </div>
    </div>
</template>

<style scoped>
    /* Tus estilos aquí */
    .vswitch-holder {
        display: flex;
        padding: 10px 20px;
        border-radius: 10px;
        justify-content: space-between;
        border: 1px solid #e0e6ed;
        align-items: center;
    }

    .vswitch-label {
        padding: 0 20px 0 10px;
        font-size: 18px;
    }

    .vswitch-label i {
        margin-right: 5px;
    }

    .vswitch-toggle {
        height: 40px;
    }

    .vswitch-toggle input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        z-index: -2;
    }

    .vswitch-toggle input[type="checkbox"]+label {
        position: relative;
        display: inline-block;
        width: 100px;
        height: 40px;
        border-radius: 20px;
        margin: 0;
        cursor: pointer;
        box-shadow: inset -8px -8px 15px rgba(255, 255, 255, .6),
            inset 10px 10px 10px rgba(0, 0, 0, .25);
        border: 1px solid #e0e6ed;
    }

    .vswitch-toggle input[type="checkbox"]+label::before {
        position: absolute;
        content: 'OFF';
        font-size: 13px;
        text-align: center;
        line-height: 25px;
        top: 8px;
        left: 8px;
        width: 45px;
        height: 25px;
        border-radius: 20px;
        background-color: #eeeeee;
        box-shadow: -3px -3px 5px rgba(255, 255, 255, .5),
                3px 3px 5px rgba(0, 0, 0, .25);
        transition: .3s ease-in-out;
    }

    .vswitch-toggle input[type="checkbox"]:checked+label::before {
        left: 50%;
        content: 'ON';
        color: #fff;
        background-color: #00b33c;
        box-shadow: -3px -3px 5px rgba(255, 255, 255, .5),
                3px 3px 5px #00b33c;
    }
    .dark .vswitch-holder{
        border: 1px solid #1e436d;
    }
    .dark .vswitch-toggle input[type="checkbox"]+label {
        box-shadow: inset -8px -8px 15px rgba(82, 80, 97, 0.411),
            inset 10px 10px 10px rgba(75, 77, 104, 0.25);
        border: 1px solid #1e436d;
    }

    .dark .vswitch-toggle input[type="checkbox"]:checked+label::before {
        color: #fff;
        background-color: #55596d;
        box-shadow: -3px -3px 5px rgba(107, 115, 150, 0.5),
                3px 3px 5px #394d64;
    }

    .dark .vswitch-toggle input[type="checkbox"]+label::before {
        background-color: #c7b2e2;
        box-shadow: -3px -3px 5px rgba(255, 255, 255, .5),
                3px 3px 5px rgba(90, 75, 124, 0.747);
        transition: .3s ease-in-out;
    }
    
</style>