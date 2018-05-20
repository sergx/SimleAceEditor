<?php $time_start = microtime(true); 

include 'simpleAceEditor.class.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>SimpleAceEditor</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"> 
  <link rel="stylesheet" href="css/font-awesome.min.css"> 
  <!-- development version, includes helpful console warnings -->
  <script src="//cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
  <script src="//unpkg.com/vue-router/dist/vue-router.js"></script>
  <script src="//unpkg.com/axios/dist/axios.min.js"></script>
  <script src="//cloud9ide.github.io/emmet-core/emmet.js"></script>
  <script src="js/require.js" type="text/javascript" charset="utf-8"></script>
  <link rel="stylesheet" href="bulma/css/bulma.css">
  <link rel="stylesheet" href="<? echo $ce->fileCasheFix('css/style.css'); ?>">
   
  <!--
  Задачи проекта
   - Открыть в отдельном окне
   - Создать файл
   - Переместить файл/папку
   - Кнопки действия: Сохранить как
   - Вверху, над списком файлов - панель инструментов со всеми этими действиями (Создать / Переместить / Архивировать)
   
  Дополнительно:
   - ВебВоркером следить за тем, не изменился ли файл. К примеру другим разработчиком он мог отредактироваться. То же - просто папки и другие файлы. Типа если изменился файл, то он как-то помечается - меняется цвет тега с размером файла.
   - При нажитии на tag с кол-вом файлов - перезагрузить этот элемент.
   - Заархивировать, скачать
   - Заказать, разархивировать
   - ? вкладки...
  -->
  
</head>
<body>
  <script>
    var editor;
    var modelist/* = ace.require("ace/ext/modelist")*/;
    require.config({paths: { "ace" : "ace/lib/ace"}});
  </script>
  <!-- load ace emmet extension -->
  <style>
  
  </style>
<div class="columns main-colums is-marginless" id="files_control">
  <div class="column is-narrow is-paddingless">
    <file-list
    :allfiles="allfiles"
    :apstatus="apstatus"
    @changeselected="changeselected"
    ></file-list>
  </div>
  <div class="column is-paddingless editor-column" :class="{saved: loadedfile.changed === false}">
    <div class="editor_title">
      <div class="field is-grouped">
        <div class="control field has-addons is-expanded">
          <p class="control">
            <a class="button is-static">
              {{apstatus.activeFile.dirname}}
            </a>
          </p>
          <p class="control is-expanded">
            <input class="input" type="text" v-model.lazy="apstatus.activeFile.basename">
          </p>
        </div>
        <p class="control">
          <span class="button is-success" @click="saveFile" :disabled="!loadedfile.changed">
            <span class="icon is-small">
              <i class="fa fa-check"></i>
            </span>
            <span>Save</span>
          </span>
          <!-- is-loading -->
          <a class="button is-success" title="Disabled button" disabled>Download</a>
          <span class="button is-danger" title="Disabled button" @click="deleteFile">Delete</span>
          <a :href="['/'+apstatus.activeFile.dirname+apstatus.activeFile.basename]"  target="_blank" class="button is-success" title="Disabled button">Open</a>
        </p>
      </div>
    </div>
    <div id="editor"></div>
  </div>
</div>

<template id="file-list-template">
  <div class="file-list">
    <ul>
      <li v-for="(file, key, index)  in allfiles">
        <file-list-item
          :file="file"
          :key="index"
          :allfiles="allfiles"
          :apstatus="apstatus"
          @changeselected="changeselected"
        ></file-list-item>
      </li>
    </ul>
  </div>
</template>
<?php // echo $_SERVER['PHP_SELF']; ?>
<template id="file-list-item-template">
  <div :class="['icon_'+file.extension]">
    <a :href="'<?php echo $_SERVER['PHP_SELF']; ?>?dirname='+file.dirname+'&basename='+file.basename" class="filename" @click="handleClick" @click.middle="handleMiddleClick" :class="{active:isOpen, is_folder:isFolder, is_file:!isFolder}">
      <span class="file_basename tag" :class="{'is-primary':isOpen, 'is-white':!isOpen && !fileInEditor, 'is-info':fileInEditor}">
        <i class="fa fa-caret-right" v-if="isFolder"  :class="{'fa-rotate-90':isOpen}"></i>
        {{file.basename}} <!--{{selected}} {{_uid}} {{fileInEditor}}-->
        <i class="fa fa-pencil" v-if="fileInEditor"></i>
      </span>
      <span class="tag is-light" v-if="childrenCount !== undefined">{{childrenCount}}</span>
      <span class="tag is-light" v-if="!isFolder">{{file.filesize}}</span>
    </a>
    <ul v-if="isOpen">
      <li v-for="(file, key, index) in file.children">
        <file-list-item
          :file="file"
          :key="index"
          :allfiles="allfiles"
          :apstatus="apstatus"
          @changeselected="changeselected"
        ></file-list-item>
      </li>
    </ul>
  </div>
</template>



<script>
  var activeFileAtStart = false;
  
  Vue.component('file-list', {
    template: '#file-list-template'
    ,props: [
      'allfiles'
      ,'apstatus'
    ]
    ,methods:{
      changeselected: function(apstatus){
        this.$emit('changeselected', apstatus);
      }
    }
  });

  Vue.component('file-list-item', {
    template: '#file-list-item-template'
    ,props: [
      'file'
      ,'allfiles'
      ,'apstatus'
    ],
    data: function () {
      return {
        isOpen: false
      }
    },
    mounted:function () {
      if(Object.keys(this.apstatus.fileonload).length){
        if(this.file.is_dir){
          if(
              (
                this.file.basename.length &&
                this.apstatus.fileonload.dirname.indexOf(this.file.dirname+this.file.basename+"/") === 0
              ) || this.file.basename ===this.apstatus.fileonload.basename
          ){
            this.loadFolder();
          }
        }else{
          if(
            this.apstatus.fileonload.dirname === this.file.dirname &&
            this.file.basename ===this.apstatus.fileonload.basename
            ){
              let thisX = this;
              // TODO fix this:
              var loadFileByLink = setInterval(function() {
                if(thisX.apstatus.editormounted){
                  clearInterval(loadFileByLink);
                  thisX.loadFile();
                }
              }, 100);
            }
        }
      }
      //if(this.file.is_dir){
        //console.log(this.file);
      //}
    },
    computed: {
      // computed обновляются только когда их "формирователи" изменяются
      isFolder: function () {
        return this.file.is_dir;
      }
      ,childrenCount: function () {
        return this.file.children && this.file.children.length;
      }
      ,fileInEditor: function(){
        return this.apstatus.selected === this._uid;
      }
      //,fileDeleted: function(){
      //  return this.apstatus.deleted.indexOf(this._uid) !== -1;
      //}
    },
    watch:{
      //'apstatus.activeFile.basename': function(newV, oldV){
      //  console.log(this.apstatus.activeFile.dirname+newV);
      //  if(this.activeFileAtStart){
      //    console.log(activeFileAtStart.basename);
      //  }
      //}
    },
    methods:{
      myTest:function(){
        //console.log(this.apstatus);
      },
      handleClick: function(e){
        e.preventDefault();
        if(this.isFolder){
          return this.loadFolder();
        }else{
          return this.loadFile();
        }
      },
      handleMiddleClick: function(){
        console.log("handleMiddleClick");
      },
      loadFolder: function(){
        let thisX = this;
        if(thisX.childrenCount || thisX.childrenCount === 0 && thisX.isOpen){
          // Если уже загружен список файлов для папки
          thisX.isOpen = !thisX.isOpen;
        }else{
          axios.post(
              'simpleAceEditor.class.php',
              {
                action : "getFolder",
                data : thisX.file.dirname + thisX.file.basename + "/"
              }
            ).then(function (response) {
              Vue.set(thisX.file, 'children', response.data);
              Vue.set(thisX, 'isOpen', true);
            }).catch(function (error) {});
        }
      },
      loadFile: function(){
        /* TODO:
            Фильтровать файлы разного типа. Загружить только текстовые.
            Выводить предупреждение, если это неизвестный тип файла, или файл слишком большой
            Сохранить файл по Ctrl+S
            Отобразить статус сохранен ли файл, или нет (свет кнопки, или обводки)
            Если начали редактировать файл, и не сохранили его, то при переключении на другой файл выводить предупреждение.
        */
        let thisX = this;
        let dataToSend = {
          action : "getFile",
          data : thisX.file.dirname + thisX.file.basename
        }
        let confirmLoad = true;
        //console.log(thisX.file);
        
        // If file is to big
        if(thisX.file.filesize.indexOf("Mb") !== -1 || thisX.file.filesize.indexOf("Gb") !== -1){
          if(!confirm("File size of "+thisX.file.basename+" is "+thisX.file.filesize+". Load anyway?")){
            return;
          }
        }
        
        // If current file wasn't saved
        if(vm.loadedfile.changed){
          if(!confirm("Changes in "+thisX.apstatus.activeFile.basename+" wasn't saved. Load another file?")){
            return;
          }
        }

        axios.post('simpleAceEditor.class.php', dataToSend, { responseType: 'text' } )
          .then(function (response) {
            activeFileAtStart = JSON.parse(JSON.stringify(thisX.file));
            /*
            let apstatus = {
              activeFile:thisX.file,
              selected:thisX._uid,
              deleted:thisX.apstatus.deleted
            };
            */
            let apstatus = thisX.apstatus;
            apstatus.activeFile = thisX.file;
            apstatus.selected = thisX._uid;
            
            let mode = modelist.getModeForPath(dataToSend.data);
            editor.session.setMode(mode.mode);
            editor.setValue(response.data);
            editor.clearSelection();
            thisX.$emit('changeselected', apstatus);
            
            document.title = thisX.file.basename + " ● " + (thisX.file.dirname.length ? thisX.file.dirname : "/") ;
            router.push({ query: { dirname: thisX.file.dirname, basename: thisX.file.basename }});
          })
          .catch(function (error) {console.error(error); });
      },
      renameFile: function(){
        
      },
      /*
      ,deleteFile: function(){
        
      }
      ,copyFile: function(){
        
      }
      ,saveAsFile: function(){
        
      }
      ,startNewFile: function(){
        
      }
      */
      changeselected:function(apstatus){
        this.$emit('changeselected', apstatus);
      }
    }
  });
  const router = new VueRouter({mode: 'history'});
  var vm = new Vue({
    el: "#files_control",
    router: router,
    data: {
      allfiles: <? echo $ce->fileList(); ?>,
      apstatus:{
        activeFile: {
          dirname: "",
          basename: ""
        },
        selected: undefined,
        deleted: [999999],
        fileonload: {},
        editormounted: false
      },
      loadedfile:{
        changed: undefined
      }
    },
    beforeCreate: function(){
      //

    },
    created: function(){
      this.apstatus.fileonload = this.$route.query;
    },
    //computed: {
    //  "apstatus.fileonload" : function(){
    //    return this.$route.query;
    //  }
    //},
    mounted () {
      // https://ace.c9.io/demo/autoresize.html
      // https://github.com/ajaxorg/ace/wiki/Configuring-Ace
      // var aceU;
      let thisX = this;
      require(["ace/ace", "ace/ext/emmet", "ace/ext/modelist"], function(ace) {
        modelist = ace.require("ace/ext/modelist");
        editor = ace.edit("editor", {
          wrapBehavioursEnabled: true,
          wrap: true,
          tabSize:2
        });
        editor.setTheme("ace/theme/chrome");
        editor.session.setMode("ace/mode/html");
        editor.setOption("enableEmmet", true);
        editor.commands.addCommand({
          name: "saveFile",
          bindKey: {win: "Ctrl-s", mac: "Command-s"},
          exec: function(editor) {
            thisX.saveFile();
          }
        });
        console.log("editor mounted");
        thisX.apstatus.editormounted = true;
      });

      //console.log("editor mounted");
      //this.apstatus.fileonload = this.$route.query;
    },
    methods: {
      saveFile: function(){
        if(!this.apstatus.activeFile.basename.length){
          console.log("Nothing to save");
          return;
        }
        if(!this.loadedfile.changed){
          console.log("File was not changed");
          return;
        }
        let thisX = this;
        //console.log(editor.getValue());
        let dataToSend = {
            action: "saveFile",
            data: {
              oldfilename: activeFileAtStart.dirname+activeFileAtStart.basename,
              filename: thisX.apstatus.activeFile.dirname+thisX.apstatus.activeFile.basename,
              content: editor.getValue()
            }
          }
        axios.post( 'simpleAceEditor.class.php', dataToSend)
          .then(function (response) {
            if(response.data.error.lenght){
              console.log(response.data.error);
              alert("Error in console");
              return;
            }
            thisX.loadedfile.changed = false;
            
            let mode = modelist.getModeForPath(dataToSend.data.filename);
            editor.session.setMode(mode.mode);
            
            document.title = thisX.apstatus.activeFile.basename + " ● " +  (thisX.apstatus.activeFile.dirname.length ? thisX.apstatus.activeFile.dirname : "/");
          })
          .catch(function (error) {console.log(error);});
          
        //console.log(editor.getValue());
      },
      deleteFile: function(){
        alert("deleteFile function is disabled for security reason");
        return;
        if(!activeFileAtStart.basename.length){
          console.log("Nothing to delete");
        }
        let thisX = this;
        
        axios.post(
          'simpleAceEditor.class.php',
          {
            action: "deleteFile",
            data: {
              pathinfo: activeFileAtStart
            }
          }
        )
          .then(function (response) {
            if(response.data.error.length){
              console.log(response.data.error);
              alert("Error here..");
              return;
            }
            alert("File deleted");
            location.reload();
          })
          .catch(function (error) {console.log(error);});
          
        //console.log(editor.getValue());
      },
      changeselected: function(apstatus){
        let thisX = this;
        thisX.apstatus = apstatus;
        thisX.loadedfile.changed = false;
        thisX.apstatus.fileonload = {};
        editor.on('change', function() {
          thisX.loadedfile.changed = true;
        });
      }
    }
  });
</script>
<script>
  console.info('%c <? echo "PHP: ". intval((microtime(true) - $time_start)*10000,10)/10000 ." сек"; ?> ', 'background:#91e8b0;font-weight:bold;');
</script>
</body>
</html>