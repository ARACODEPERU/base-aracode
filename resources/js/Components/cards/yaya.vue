<script setup>
  defineProps({
    title:{
      type: String,
      default: null
    }
  });
</script>
<template>
    <!-- From Uiverse.io by Yaya12085 --> 
    <div class="ycard">
        <div class="yicon">
            <img src="/img/svg/certificate-1356.svg" alt="">
        </div>
        <div class="ycontent">
            <span class="ytitle">{{ title }}</span>
            <!-- <div class="ydesc">This software is now available for download.</div>  -->
            <div class="yactions">
                <div>
                    <a href="#" class="ydownload">
                      <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><g stroke-width="0" id="SVGRepo_bgCarrier"></g><g stroke-linejoin="round" stroke-linecap="round" id="SVGRepo_tracerCarrier"></g><g id="SVGRepo_iconCarrier"> <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" stroke="#ffffff" d="M20 14V17.5C20 20.5577 16 20.5 12 20.5C8 20.5 4 20.5577 4 17.5V14M12 15L12 3M12 15L8 11M12 15L16 11"></path> </g></svg>
                      Descargar
                    </a>
                </div>
            </div>    
        </div>

    </div>
</template>
<style>
/* From Uiverse.io by Yaya12085 */ 
.ycard {
  max-width: 320px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  border-radius: 0.5rem;
  background: #606c88;
  background: -webkit-linear-gradient(to right, #3f4c6b, #606c88);
  background: linear-gradient(to right top, #3f4c6b, #606c88);
  padding: 1rem;
  color: rgb(107, 114, 128);
  box-shadow: 0px 87px 78px -39px rgba(0,0,0,0.4);
}

.yicon {
  height: 3rem;
  width: 3rem;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  background-color: #153586;
  color: rgb(59, 130, 246);
  padding: 4px;
}

.yicon svg {
  height: 1.25rem;
  width: 1.25rem;
}

.ycontent {
  margin-left: 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  font-weight: 400;
}

.ytitle {
  margin-bottom: 0.25rem;
  font-size: 1.5rem;
  line-height: 1.25rem;
  font-weight: 600;
  color: rgb(255, 255, 255);
}

.ydesc {
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  font-weight: 400;
  color: rgb(202, 200, 200);
}

.yactions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-gap: 0.5rem;
  gap: 0.5rem;
}

.ydownload, .ynotnow {
  width: 100%;
  display: inline-flex;
  justify-content: center;
  border-radius: 0.5rem;
  padding: 0.375rem 0.5rem;
  text-align: center;
  font-size: 0.75rem;
  line-height: 1rem;
  color: rgb(255, 255, 255);
  outline: 2px solid transparent;
  border: 1px solid rgba(253, 253, 253, 0.363);
}

.ydownload {
  background-color: #284385;
  font-weight: 600;
}

.ydownload:hover {
  background-color: #153586;
}

.ynotnow {
  background-color: #606c88;
  font-weight: 500;
}

.ynotnow:hover {
  background-color: #3f4c6b;
}

.yclose {
  margin: -0.375rem -0.375rem -0.375rem auto;
  height: 2rem;
  width: 2rem;
  display: inline-flex;
  border-radius: 0.5rem;
  background-color: #606c88;
  padding: 0.375rem;
  color: rgba(255, 255, 255, 1);
  border: none;
}

.yclose svg {
  height: 1.25rem;
  width: 1.25rem;
}

.yclose:hover {
  background-color: rgb(58, 69, 83);
}
</style>