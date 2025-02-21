<template>
    <div class="kcards">
        <div class="kcard kred">
            <p class="ktip">Hover Me</p>
            <p class="ksecond-text">Lorem Ipsum</p>
        </div>
        <div class="kcard kblue">
            <p class="ktip">Hover Me</p>
            <p class="ksecond-text">Lorem Ipsum</p>
        </div>
        <div class="kcard kgreen">
            <p class="ktip">Hover Me</p>
            <p class="ksecond-text">Lorem Ipsum</p>
        </div>
    </div>
</template>
<style>
.kcards {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.kcards .kred {
  background-color: #f43f5e;
}

.kcards .kblue {
  background-color: #3b82f6;
}

.kcards .kgreen {
  background-color: #22c55e;
}

.kcards .kcard {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  text-align: center;
  height: 100px;
  width: 250px;
  border-radius: 10px;
  color: white;
  cursor: pointer;
  transition: 400ms;
}

.kcards .kcard p.ktip {
  font-size: 1em;
  font-weight: 700;
}

.kcards .kcard p.ksecond-text {
  font-size: .7em;
}

.kcards .kcard:hover {
  transform: scale(1.1, 1.1);
}

.kcards:hover > .kcard:not(:hover) {
  filter: blur(10px);
  transform: scale(0.9, 0.9);
}
</style>