
import Vue from 'vue';
export const emitter = new Vue()

export default {
  namespaced: true,

  state: {
    field: {},
    images: [],
    isCollection: false,
    modal:{
      header: '',
      message: '',
      showConfirm: false,
      show: false
    },
    imageValidators: []
  },

  mutations: {
    initialize(state, payload) {
      state.field = payload.field;
      state.images = payload.images
      state.isCollection = payload.isCollection
    },

    updateImages(state, images) {
      state.images = images
    },

    updateModal(state, modal) {
      state.modal = modal;
    },

    pushImageValidation(state, callbacks) {
      callbacks.forEach((validator)=>{
        if(typeof validator.condition == "undefined" || typeof validator.modal == "undefined" ) {
          throw 'Callback must have a condition and modal properties set.';
        }
        state.imageValidators.push(validator);
      })
    }
  },

  actions: {
    addImageFromFile ({ state, commit, dispatch }, payload) {
      let file = payload.file
      let imageUrl = URL.createObjectURL(file)
      let metadata = payload.hasOwnProperty('metadata') ? payload.metadata : []

      let image = {
        inputId: 'eloquent-imagery-' + state.field.name + '-' + (state.images.length + 1),
        previewUrl: imageUrl,
        thumbnailUrl: imageUrl,
        metadata: Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]})),
      }

      let modalPromise = new Promise((resolve) => {
        resolve(true);
      });

      state.imageValidators.forEach((imageValidator)=> {
        if(!imageValidator.condition(file,state.field)) {
          return;
        }

        let modal = imageValidator.modal(file,state.field)
        modalPromise = dispatch('showModal',modal);
      });

      return modalPromise.then((shouldLoadImage)=> {
        if(!shouldLoadImage){
          return false;
        }

        let images = state.images
        images.push(image)

        commit('updateImages', images)

        return new Promise((resolve, reject) => {
          let reader = new FileReader()

          reader.addEventListener('load', () => {
            image.fileData = reader.result

            resolve(image)
          })

          reader.readAsDataURL(file)
        })
      });
    },

    addImage ({ state, commit }, image) {
      let images = state.images
      images.push(image)

      commit('updateImages', images)
    },

    removeImage ({ state, commit }, imageToRemove) {
      commit('updateImages', state.images.filter(image => image !== imageToRemove))
    },

    notifyCloseModalEvent({state}, modalOption) {
      emitter.$emit('close',modalOption);
    },

    showModal({ state, commit }, modal){
      commit('updateModal',modal);

      return new Promise((resolve, reject) =>  {
        state.modal.show = true;
        emitter.$on('close', (modalOption) => {
          resolve(modalOption);
        });
      });
    },
  },

  getters: {
    getImages: (state) => state.images,
    getIsCollection: (state) => state.isCollection,
    getModal: (state) => state.modal
  }
}
