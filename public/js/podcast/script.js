// Song data

document.addEventListener('DOMContentLoaded', function() {
    var ul = document.getElementById('songs_user');
    var lista = ul.getElementsByTagName('li');
      
    var songList = [];

    for (var i = 0; i < lista.length; i++) {
        let id = lista[i].querySelector('.id_song').textContent
        let title = lista[i].querySelector('.title_song').textContent
        let cover = lista[i].querySelector('.cover_song').textContent
        let file = lista[i].querySelector('.audio_song').textContent

        songList.push({
            id: id,
            title: title,
            file: file,
            cover: cover
        });
    }

    // Canción actual
    let actualSong = null

    // Capturar elementos del DOM para trabajar con JS
    const songs = document.getElementById("songs")
    const audio = document.getElementById("audio")
    const cover = document.getElementById("cover")
    const title = document.getElementById("title")
    const play = document.getElementById("play")
    const prev = document.getElementById("prev")
    const next = document.getElementById("next")
    const progress = document.getElementById("progress")
    const progressContainer = document.getElementById("progresscontainer")
    progressContainer.addEventListener("click", setProgress)

    // Escuchar el elemento AUDIO
    audio.addEventListener("timeupdate", updateProgress)

    // Escuchar clicks en los controles
    play.addEventListener("click", () => {
        if (audio.paused) {
            playSong()   
        } else {
            pauseSong()
        }
    })

    next.addEventListener("click", () => nextSong())
    prev.addEventListener("click", () => prevSong())

    // Cargar canciones y mostrar el listado
    function loadSongs() {
        songList.forEach((song, index) => {
            // Crear li
            const li = document.createElement("li")
            li.classList.add("d-flex")
            li.classList.add("justify-content-between");
            li.classList.add("align-items-center");
            // Crear a
            const link = document.createElement("a")
            // Hidratar a
            link.textContent = song.title
            link.href = "#"
            // Escuchar clicks
            link.addEventListener("click", () => loadSong(index))
            // Añadir a li
            li.appendChild(link)
            $(li).append(
                $('<div>',{
                    'class': 'buttons-actions' 
                }).append(
                    $('<a>',{
                        'class': 'btn btn-warning m-1 edit-podcast',
                        'href': '/podcast/edit/'+song.id,
                        'text': 'Edit'
                    }),
                    $('<a>',{
                        'class': 'btn btn-danger m-1 delete-podcast',
                        'href': '#',
                        'text': 'Delete',
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#exampleModal2',
                        'aria-current': 'page',
                        'onclick': 'delete_podcast('+song.id+')'
                    })
                )
            );
            // Aañadir li a ul
            songs.appendChild(li)
        })
    }

    // Cargar canción seleccionada
    function loadSong(songIndex) {
        if (songIndex !== actualSong) {
            changeActiveClass(actualSong, songIndex)
            actualSong = songIndex
            audio.src = songList[songIndex].file
            playSong()
            changeSongtitle(songIndex)
            changeCover(songIndex)
        }
    }

    // Actualizar barra de progreso de la canción
    function updateProgress(event) {
        const {duration, currentTime} = event.srcElement
        const percent = (currentTime / duration) * 100
        progress.style.width = percent + "%" 
    }

    // Hacer la barra de progreso clicable
    function setProgress(event) {
        const totalWidth = this.offsetWidth
        const progressWidth = event.offsetX
        const current = (progressWidth / totalWidth) * audio.duration
        audio.currentTime = current
    }

    // Actualiar controles
    function updateControls() {
        if (audio.paused) {
            play.classList.remove("fa-pause")
            play.classList.add("fa-play")
        } else {
            play.classList.add("fa-pause")
            play.classList.remove("fa-play")
        }
    }

    // Reproducir canción
    function playSong() {
        if (actualSong !== null) {
            audio.play()
            updateControls()
        }
    }

    // Pausar canción
    function pauseSong() {
        audio.pause()
        updateControls()
    }

    // Cambiar clase activa
    function changeActiveClass(lastIndex, newIndex) {
        const links = document.querySelectorAll("#songs li>a")
        if (lastIndex !== null) {
            links[lastIndex].classList.remove("active")
        }
        links[newIndex].classList.add("active")
    }

    // Cambiar el cover de la canción
    function changeCover(songIndex) {
        cover.src = songList[songIndex].cover
    }

    // Cambiar el título de la canción
    function changeSongtitle(songIndex) {
        title.innerText = songList[songIndex].title
    }

    // Anterior canción
    function prevSong() {
        if (actualSong > 0) {
            loadSong(actualSong - 1)
        } else {
            loadSong(songList.length - 1)
        }
    }

    // Siguiente canción
    function nextSong() {
        if (actualSong < songList.length -1) {
            loadSong(actualSong + 1)
        } else {
            loadSong(0)
        }
    }

    // Lanzar siguiente canción cuando se acaba la actual
    audio.addEventListener("ended", () => nextSong())

    
    loadSongs()
   
});

function delete_podcast ($id) {
    let url = document.getElementById("podcast-delete"); 
    var parts = url.href.split("/");
    parts[parts.length - 1] = $id;
    newUrl = parts.join("/")
    url.setAttribute('href', newUrl);
    
}
