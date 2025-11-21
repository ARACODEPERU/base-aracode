<script setup>
    import { ref, watch } from 'vue'

    const props = defineProps({
        invoice: {
            type: Object,
            default: () => ({ courses: [] })
        },
        igv: {
            type: Number,
            default: 18 // Valor por defecto si no se pasa desde el padre
        }
    })

    // Totales reactivos
    const totalVenta = ref(0)
    const totalIgv = ref(0)
    const totalPagar = ref(0)

    const calcularTotales = (courses = [], subscriptions = []) => {
        let totalCursos = 0
        let totalSubscription = 0

        // 🔹 Total de cursos
        courses.forEach(item => {
            const rawPrice = item.price ?? item.precio ?? item.amount ?? 0
            const qty = item.qty ?? item.quantity ?? item.cantidad ?? 1

            totalCursos += (Number(rawPrice) || 0) * (Number(qty) || 1)
        })

        // 🔹 Total de suscripciones
        subscriptions.forEach(item => {
            const rawPrice = item.price ?? item.precio ?? item.amount ?? 0
            const qty = item.qty ?? item.quantity ?? item.cantidad ?? 1

            totalSubscription += (Number(rawPrice) || 0) * (Number(qty) || 1)
        })

        // 🔹 Total general
        const total = totalCursos + totalSubscription

        // 🔹 Cálculo del IGV dinámico (por ejemplo 18%)
        const divisor = 1 + (props.igv / 100)
        const valorVenta = total / divisor
        const igvValue = total - valorVenta

        totalVenta.value = Math.round((valorVenta + Number.EPSILON) * 100) / 100
        totalIgv.value = Math.round((igvValue + Number.EPSILON) * 100) / 100
        totalPagar.value = Math.round((total + Number.EPSILON) * 100) / 100
    }

    // 👇 Watch reactivo a cambios en cursos o suscripciones
    watch(
        [() => props.invoice.courses, () => props.invoice.subscriptions],
        ([courses, subscriptions]) => {
            if (!Array.isArray(courses)) courses = []
            if (!Array.isArray(subscriptions)) subscriptions = []
            calcularTotales(courses, subscriptions)
        },
        { deep: true, immediate: true }
    )

</script>
<template>
    <div class="w-full">

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="space-y-1">
                <div class="text-xl font-semibold uppercase">Datos del Alumno</div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Nombre Completo:</div>
                    <div class="whitespace-nowrap">{{ `${invoice.afather} ${invoice.amother} ${invoice.names}` }}</div>
                </div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Número de identificación:</div>
                    <div class="whitespace-nowrap">{{ invoice.alu_number }}</div>
                </div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Dirección:</div>
                    <div class="whitespace-nowrap">{{ invoice.address }}</div>
                </div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Ciudad:</div>
                    <div class="whitespace-nowrap">{{ invoice.ubigeo_description }}</div>
                </div>

            </div>
            <div class="space-y-1">
                <div class="text-xl font-semibold uppercase">Datos del Cliente</div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Razón social:</div>
                    <div class="whitespace-nowrap">{{ invoice.sale_full_name }}</div>
                </div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">DNI / RUC:</div>
                    <div>{{ invoice.number }}</div>
                </div>
                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Dirección fiscal:</div>
                    <div>{{ invoice.sale_address }}</div>
                </div>

                <div class="flex items-center w-full justify-between mb-2">
                    <div class="text-white-dark">Ciudad:</div>
                    <div>{{ invoice.sale_ubigeo_description }}</div>
                </div>
            </div>
        </div>
        <div class="table-responsive mt-6">
            <table>
                <thead>
                    <tr>
                        <th>Curso o Suscripción</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="invoice.courses">
                        <tr v-for="item in invoice.courses" :key="item.id">
                            <td>{{ item.description }}</td>
                            <td class="ltr:text-right rtl:text-left">S/.{{ item.price }}</td>
                        </tr>
                    </template>
                    <template v-if="invoice.subscriptions">
                        <tr v-for="item in invoice.subscriptions" :key="item.id">
                            <td>{{ item.description }}</td>
                            <td class="ltr:text-right rtl:text-left">S/.{{ item.price }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="grid sm:grid-cols-2 grid-cols-1 px-4 mt-6">
            <div></div>
            <div class="ltr:text-right rtl:text-left space-y-2">
                <div class="flex items-center">
                    <div class="flex-1">Subtotal</div>
                    <div class="w-[37%]">S/. {{ totalVenta }}</div>
                </div>
                <div class="flex items-center">
                    <div class="flex-1">IGV</div>
                    <div class="w-[37%]">S/. {{ totalIgv }}</div>
                </div>
                <div class="flex items-center font-semibold text-lg">
                    <div class="flex-1">Total</div>
                    <div class="w-[37%]">S/. {{ totalPagar }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
