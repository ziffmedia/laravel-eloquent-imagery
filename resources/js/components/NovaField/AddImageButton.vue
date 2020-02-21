<template>
  <div class="pl-1 pr-1 border border-70 flex items-center m-1">
    <div class="px-6 py-4 content-center">
      <input
        ref="addNewImageFileInput"
        class="form-file-input select-none"
        type="file"
        v-on:change="(e) => $emit('image-added', e)"
      />

      <span v-on:click="() => this.$refs['addNewImageFileInput'].click()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height="72" width="72">
          <path fill="#888" d="M6 2h9a1 1 0 0 1 .7.3l4 4a1 1 0 0 1 .3.7v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2zm9 2.41V7h2.59L15 4.41zM18 9h-3a2 2 0 0 1-2-2V4H6v16h12V9zm-5 4h2a1 1 0 0 1 0 2h-2v2a1 1 0 0 1-2 0v-2H9a1 1 0 0 1 0-2h2v-2a1 1 0 0 1 2 0v2z"/>
        </svg>
      </span>
    </div>
  </div>
</template>

<script>
  export default {

  }
</script>
