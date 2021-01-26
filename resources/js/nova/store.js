
import Vue from 'vue';
export const emitter = new Vue()

export default {
  namespaced: true,

  state: {
    field: {},
    images: [],
    isCollection: false,
    validationRules: [],
    validationUi: {
      errorText: '',
      modal: {
        show: false,
        headerText: '',
        bodyText: '',
        handleConfirm: null,
        handleCancel: null
      }
    },
  },

  mutations: {
    initialize (state, payload) {
      state.field = payload.field;
      state.images = payload.images
      state.isCollection = payload.isCollection
      state.validationRules = payload.validationRules
    },

    updateImages (state, images) {
      state.images = images
    },

    updateValidationUiErrorText (state, errorText) {
      state.validationUi.errorText = errorText
    },

    updateValidationUiModal (state, modal) {
      Object.assign(state.validationUi.modal, modal ?? {
        show: false,
        headerText: '',
        bodyText: '',
        handleConfirm: null,
        handleCancel: null
      })
    }
  },

  actions: {
    addImageFromFile ({ state, commit, dispatch }, payload) {
      const file = payload.file
      const imageUrl = URL.createObjectURL(file)
      const metadata = payload.hasOwnProperty('metadata') ? payload.metadata : []

      const image = {
        inputId: 'eloquent-imagery-' + state.field.name + '-' + (state.images.length + 1),
        previewUrl: imageUrl,
        thumbnailUrl: imageUrl,
        metadata: Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]})),
      }

      return new Promise((resolve, reject) => {

        // file type validation
        if (state.validationRules.hasOwnProperty('type_limit') && !state.validationRules['type_limit'].types.includes(file.type)) {
          if (state.validationRules['type_limit'].ui !== 'modal') {
            commit('updateValidationUiErrorText', 'A ' + file.type + ' file is unsupported.')

            // ensure message goes away after 5 seconds
            setTimeout(() => commit('updateValidationUiErrorText'), 5000)

            return reject()
          }

          commit('updateValidationUiModal', {
            show: true,
            headerText: 'A ' + file.type + ' file is unsupported.',
            bodyText: 'An image must be one of the following formats: ' + state.validationRules['type_limit'].types.join(', '),
            handleCancel: () => {
              commit('updateValidationUiModal')

              reject()
            }
          })

          return
        }

        // size limit validation
        if (state.validationRules.hasOwnProperty('size_limit')) {
          // hard limit, no modal
          if (state.validationRules['size_limit'].hasOwnProperty('hard_limit')
            && file.size > state.validationRules['size_limit']['hard_limit']
          ) {
            if (state.validationRules['size_limit']['hard_limit_ui'] !== 'modal') {
              commit('updateValidationUiErrorText', 'The file (' + file.size + ' bytes) is too large to upload, files must be less than ' + state.validationRules['size_limit']['hard_limit'])

              // ensure message goes away after 5 seconds
              setTimeout(() => commit('updateValidationUiErrorText'), 5000)

              return reject()
            }

            commit('updateValidationUiModal', {
              show: true,
              headerText: 'File is too large to upload.',
              bodyText: 'The file (' + file.size + ' bytes) is too large to upload, files must be less than ' + state.validationRules['size_limit']['hard_limit'],
              handleCancel: () => {
                commit('updateValidationUiModal')

                reject()
              }
            })

            return
          }

          // soft limit
          if (state.validationRules['size_limit'].hasOwnProperty('soft_limit')
            && file.size > state.validationRules['size_limit']['soft_limit']
          ) {
            commit('updateValidationUiModal', {
              show: true,
              headerText: 'Are you sure you want to upload this image?',
              bodyText: 'This file is (' + file.size + ' bytes) which is greater than the suggested limit size of ' + state.validationRules['size_limit']['soft_limit'],
              handleCancel: () => {
                commit('updateValidationUiModal')

                reject()
              },
              handleConfirm: () => {
                commit('updateValidationUiModal')

                resolve()
              }
            })

            return
          }
        }

        resolve()
      }).then(() => {
        return new Promise(resolve => {
          let images = state.images
          images.push(image)

          commit('updateImages', images)

          let reader = new FileReader()

          reader.addEventListener('load', () => {
            image.fileData = reader.result

            resolve(image)
          })

          reader.readAsDataURL(file)
        })
      }).catch(() => {
        return Promise.reject()
      })
    },

    addImage ({ state, commit }, image) {
      let images = state.images
      images.push(image)

      commit('updateImages', images)
    },

    removeImage ({ state, commit }, imageToRemove) {
      commit('updateImages', state.images.filter(image => image !== imageToRemove))
    },

    updateImageMetadata ({ state, commit }, payload) {
      let images = state.images;

      images.forEach((image) => {
        if (payload.inputId && image.inputId && payload.inputId === image.inputId && payload.metadata) {
          let newMetadata = Object.keys(payload.metadata).map(key => ({'key': key, 'value': payload.metadata[key]}))
          let oldMetadata = image.metadata;
          let metadata = {};

          [oldMetadata, newMetadata].forEach((arr) => {
            arr.forEach((item) => {
              metadata[item['key']] = item['value']
            })
          });

          image.metadata = Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]}))
        }
      });

      commit('updateImages', images)
    }
  },

  getters: {
    getImages: (state) => state.images,
    getIsCollection: (state) => state.isCollection,
    getModal: (state) => state.modal,
    getValidationUi: (state) => state.validationUi
  }
}
