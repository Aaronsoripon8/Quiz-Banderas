// Ejecutar la inicialización una vez el DOM esté listo para evitar errores por acceso temprano
document.addEventListener('DOMContentLoaded', () => {
  // Objeto para gestionar el estado de la partida
  let estatDeLaPartida = {
    contadorPreguntes: 0,
    respostesUsuari: []
  }

  // Referencias al DOM (inicializadas tras DOMContentLoaded)
  const btnEnviar = document.getElementById('btnEnviar')
  const tempsDiv = document.getElementById('temps')
  const usuariContainer = document.getElementById('usuariContainer')
  const formUsuari = document.getElementById('formUsuari')
  const nomUsuariInput = document.getElementById('nomUsuari')
  const salutacioDiv = document.getElementById('salutacio')
  const missatgeSalutacio = document.getElementById('missatgeSalutacio')
  const contenidor = document.getElementById('partida')
  const marcador = document.getElementById('marcador')

  // Pequeña utilidad para escapar HTML cuando se insertan cadenas proporcionadas por el usuario
  function escapeHtml (str) {
    if (!str) return ''
    return String(str).replace(/[&<>"'`=\/]/g, function (s) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      })[s]
    })
  }

  // Función para renderizar preguntas
  let preguntaActual = 0
  function renderitzarPreguntes (preguntes) {
    // Solo muestra la pregunta actual
    const i = preguntaActual
    // Usa el orden barajado precomputado si está disponible, en caso contrario usa el orden original
    const respostesBarrejades = preguntes[i]._respostesBarrejades ? preguntes[i]._respostesBarrejades : preguntes[i].respostes
    let htmlString = `
      <div class="pregunta" data-num="${i}">
        <h3 style="text-align:center;font-size:2rem;margin-bottom:1.2rem;">${preguntes[i].pregunta}</h3>
        <img src="${preguntes[i].imatge}" alt="Bandera" style="display:block;margin:0 auto 1.2rem auto;width:100%;max-width:420px;min-width:220px;height:auto;object-fit:cover;border-radius:12px;box-shadow:0 4px 18px #2563eb22;">
        <div class="respostes" style="display:grid;grid-template-columns:1fr 1fr;gap:18px 18px;margin-top:1.2rem;">
    `
    for (let j = 0; j < respostesBarrejades.length; j++) {
      const resposta = respostesBarrejades[j]
      const jaRespost = estatDeLaPartida.respostesUsuari[i] !== undefined
      const marcada = estatDeLaPartida.respostesUsuari[i] === resposta.id
      htmlString += `
        <button 
          class="btnResposta" 
          data-num="${i}" 
          data-id="${resposta.id}"
          ${segonsRestants <= 0 ? 'disabled' : ''}
          ${jaRespost ? 'disabled' : ''}
          ${marcada ? 'aria-pressed="true" style="font-weight:bold;background:#def;"' : ''}
        >
          ${resposta.etiqueta}
        </button>
      `
    }
    htmlString += '</div></div>'
    // Botones de navegación
    htmlString += '<div style="margin-top:10px;">'
    if (preguntaActual > 0) {
      htmlString += '<button id="btnAnterior">Anterior</button>'
    }
    if (preguntaActual < preguntes.length - 1) {
      htmlString += '<button id="btnSeguent">Següent</button>'
    }
    htmlString += '</div>'
    contenidor.innerHTML = htmlString
  }

  // Delegación de eventos para respuestas
  contenidor.addEventListener('click', function (event) {
    if (event.target.classList.contains('btnResposta')) {
      const numPregunta = parseInt(event.target.getAttribute('data-num'))
      const idResposta = parseInt(event.target.getAttribute('data-id'))
      seleccionarResposta(numPregunta, idResposta)
    }
    if (event.target.id === 'btnSeguent') {
      if (preguntaActual < preguntesGlobal.length - 1) {
        preguntaActual++
        renderitzarPreguntes(preguntesGlobal)
      }
    }

    // Botón en la pantalla de inicio para ver la clasificación
    const btnVerClasificacionHome = document.getElementById('btnVerClasificacionHome')
    if (btnVerClasificacionHome) {
      btnVerClasificacionHome.addEventListener('click', function () {
        window.open('./api/scores.php', '_blank', 'noopener,noreferrer')
      })
    }
    if (event.target.id === 'btnAnterior') {
      if (preguntaActual > 0) {
        preguntaActual--
        renderitzarPreguntes(preguntesGlobal)
      }
    }
  })

  function seleccionarResposta (numPregunta, idResposta) {
    if (segonsRestants <= 0) return
    // Registrar respuesta
    if (estatDeLaPartida.respostesUsuari[numPregunta] === undefined) {
      estatDeLaPartida.contadorPreguntes++
    }
    estatDeLaPartida.respostesUsuari[numPregunta] = idResposta
    renderitzarMarcador()

    // Re-render para desactivar botones y mostrar el estado
    renderitzarPreguntes(preguntesGlobal)

    // Aplicar clases de correcto/incorrecto a los botones (sin mostrar texto)
    const p = preguntesGlobal[numPregunta]
    const correcte = (p && (typeof p.resposta_correcta !== 'undefined' ? p.resposta_correcta : (p.correctIndex !== undefined ? p.correctIndex : null)))
    const botons = contenidor.querySelectorAll(`.btnResposta[data-num="${numPregunta}"]`)
    botons.forEach(btn => {
      const bid = parseInt(btn.getAttribute('data-id'))
      // Desactivar para mayor seguridad
      btn.disabled = true
      // Quitar clases previas si existen
      btn.classList.remove('correct', 'incorrect')
      if (correcte !== null && correcte !== undefined) {
        if (bid === correcte) {
          btn.classList.add('correct')
        }
        if (bid === idResposta && idResposta !== correcte) {
          btn.classList.add('incorrect')
        }
      } else {
        // Si no hay información de la correcta en la pregunta, marcamos la seleccionada como correcta
        if (bid === idResposta) btn.classList.add('correct')
      }
    })
  }

  // Función para mostrar el marcador
  function renderitzarMarcador () {
    marcador.innerHTML = `
      Preguntes respostes: ${estatDeLaPartida.contadorPreguntes} de ${totalPreguntes}
    `

    // Si el usuario ha respondido todas, mostrar botón de enviar
    if (estatDeLaPartida.contadorPreguntes === totalPreguntes) {
      btnEnviar.classList.remove('hidden')
    }
  }

  // Variable global para saber cuántas preguntas hay
  let totalPreguntes = 0
  let preguntesGlobal = []
  let intervalTemps = null
  let segonsRestants = 30 // Modo F1: 30 segundos
  let gameStarted = false // marca si el quiz ha empezado

  // --- GESTIÓN USUARIO CON LOCALSTORAGE ---
  function mostrarFormulariUsuari () {
    // Mostrar el formulario solo si el juego no ha comenzado
    if (gameStarted) return
    if (formUsuari) formUsuari.classList.remove('hidden')
    if (salutacioDiv) salutacioDiv.classList.add('hidden')
    if (usuariContainer) usuariContainer.style.display = ''
  }
  function mostrarSalutacio (nom, isReturning = false) {
    // No mostrar el formulario original cuando mostramos un saludo
    if (formUsuari) formUsuari.classList.add('hidden')
    if (salutacioDiv) salutacioDiv.classList.add('hidden')
    // Crear o actualizar un encabezado de saludo persistente encima del área del quiz
    let greeting = document.getElementById('greetingHeader')
    if (!greeting) {
      greeting = document.createElement('div')
      greeting.id = 'greetingHeader'
      greeting.style.textAlign = 'center'
      greeting.style.margin = '8px 0'
      greeting.style.display = 'flex'
      greeting.style.justifyContent = 'center'
      greeting.style.alignItems = 'center'
      greeting.style.gap = '12px'
      const page = document.querySelector('.page')
      const tempsEl = document.getElementById('temps')
      if (page) page.insertBefore(greeting, tempsEl || page.firstChild)
    }
    greeting.innerHTML = `<span style="font-weight:700;color:var(--text);">Hola, ${escapeHtml(nom)}</span><button id="btnCanviarNom" style="padding:6px 10px;border-radius:8px;border:none;background:#fff;border:1px solid var(--accent);color:var(--accent);cursor:pointer;">Canviar nom</button>`
    // Adjuntar handler para el botón de cambiar nombre
    const btnCanviar = document.getElementById('btnCanviarNom')
    if (btnCanviar) {
      btnCanviar.addEventListener('click', function () {
        // permitir cambiar nombre: detener juego y mostrar formulario
        gameStarted = false
        try { localStorage.removeItem('nomUsuari') } catch (e) {}
        if (usuariContainer) usuariContainer.style.display = ''
        if (formUsuari) formUsuari.classList.remove('hidden')
        if (greeting) greeting.remove()
        // limpiar preguntas/vista previa
        if (contenidor) contenidor.innerHTML = ''
        if (marcador) marcador.innerHTML = ''
        if (tempsDiv) tempsDiv.textContent = ''
        if (intervalTemps) { clearInterval(intervalTemps); intervalTemps = null }
      })
    }
  }
  function comprovarUsuari () {
    const nom = localStorage.getItem('nomUsuari')
    if (nom) {
      mostrarSalutacio(nom)
      iniciarJoc()
    } else {
      mostrarFormulariUsuari()
    }
  }
  if (formUsuari) {
    formUsuari.addEventListener('submit', function (e) {
      e.preventDefault()
      const nom = nomUsuariInput.value.trim()
      if (!nom) return
      try { localStorage.setItem('nomUsuari', nom) } catch (err) {}
      // Mostrar saludo por defecto (primera vez)
      mostrarSalutacio(nom, false)
      iniciarJoc()
    })
  }

  // --- TEMPORIZADOR ---
  function iniciarTemporitzador () {
    segonsRestants = 30
    if (tempsDiv) tempsDiv.textContent = `Temps restant: ${segonsRestants}s`
    if (intervalTemps) clearInterval(intervalTemps)
    intervalTemps = setInterval(() => {
      segonsRestants--
      if (tempsDiv) tempsDiv.textContent = `Temps restant: ${segonsRestants}s`
      if (segonsRestants <= 0) {
        clearInterval(intervalTemps)
        if (tempsDiv) tempsDiv.textContent = 'Temps esgotat!'
        btnEnviar.classList.remove('hidden')
        // Desactivar respuestas
        renderitzarPreguntes(preguntesGlobal)
      }
    }, 1000)
  }

  // --- INICIO JUEGO ---
  function iniciarJoc () {
    gameStarted = true // marcar juego como iniciado para que el formulario no reaparezca
    // Ocultar el contenedor original del formulario para evitar que se muestre en el diseño
    if (usuariContainer) usuariContainer.style.display = 'none'
    // Pedir 10 preguntas al servidor (BD obligatoria)
    if (tempsDiv) tempsDiv.textContent = 'Carregant preguntes des de la base de dades...'
    fetch('./api/getPreguntes.php?n=10')
      .then(res => {
        if (!res.ok) throw new Error('API retornà codi ' + res.status)
        return res.json()
      })
      .then(data => {
        if (!data || !data.preguntes) throw new Error('Resposta API invàlida')
        preguntesGlobal = data.preguntes
        // Precompute a single shuffled order per question so it doesn't change on re-renders
        preguntesGlobal.forEach(q => {
          if (Array.isArray(q.respostes)) q._respostesBarrejades = shuffleArray(q.respostes)
        })
        iniciarAmbPreguntes()
      })
      .catch(err => {
        console.error('No s’han pogut carregar preguntes des de la BD:', err)
        if (tempsDiv) tempsDiv.textContent = 'Error: no s’han pogut carregar preguntes des de la base de dades.'
        // Mostrar mensaje visible al usuario
        contenidor.innerHTML = '<div class="error"><p>No hi ha preguntes disponibles. Contacta l\'administrador.</p></div>'
      })
    function iniciarAmbPreguntes () {
      totalPreguntes = preguntesGlobal.length
      estatDeLaPartida.contadorPreguntes = 0
      estatDeLaPartida.respostesUsuari = []
      preguntaActual = 0
      renderitzarPreguntes(preguntesGlobal)
      renderitzarMarcador()
      iniciarTemporitzador()
    }
  }

  // Enviar resultados al servidor
  if (btnEnviar) btnEnviar.addEventListener('click', function () {
    // Construir array de respuestas con idPregunta y respuesta
    const payload = []
    for (let i = 0; i < preguntesGlobal.length; i++) {
      const q = preguntesGlobal[i]
      const resp = estatDeLaPartida.respostesUsuari[i]
      if (resp !== undefined) {
        payload.push({ idPregunta: q.id, resposta: resp })
      }
    }
    // Intentamos enviar al servidor; si falla, calculamos localmente
    fetch('./api/finalitza.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ respostes: payload })
    })
      .then(r => r.json())
      .then(result => {
        if (result && typeof result.correctes !== 'undefined') {
          mostrarResultatFinal(result)
        } else {
          // respuesta inesperada -> fallback local
          mostrarResultatFinal(calcularResultatLocal())
        }
      })
      .catch(err => {
        console.warn('No s’ha pogut contactar finalitza.php, calculant localment', err)
        mostrarResultatFinal(calcularResultatLocal())
      })
  })

  function calcularResultatLocal () {
    let total = 0
    let correctes = 0
    for (let i = 0; i < preguntesGlobal.length; i++) {
      const q = preguntesGlobal[i]
      const resp = estatDeLaPartida.respostesUsuari[i]
      if (typeof resp !== 'undefined') {
        total++
        if (typeof q.resposta_correcta !== 'undefined') {
          if (q.resposta_correcta == resp) correctes++
        } else if (q.correctIndex !== undefined) {
          if (q.correctIndex == resp) correctes++
        }
      }
    }
    return { total: total, correctes: correctes }
  }

  function mostrarResultatFinal (result) {
    const total = result.total || 0
    const correctes = result.correctes || 0
    contenidor.innerHTML = `<div class="final card"><h2>Resultats</h2><p>Correctes: ${correctes} / ${total}</p><button id="btnReinicia">Jugar de nou</button></div>`
    btnEnviar.classList.add('hidden')
    if (intervalTemps) clearInterval(intervalTemps)
    if (tempsDiv) tempsDiv.textContent = ''
    document.getElementById('btnReinicia').addEventListener('click', () => {
      iniciarJoc()
      iniciarTemporitzador()
    })
    // Guardar puntuacion en el servidor y mostrar leaderboard
    const nom = localStorage.getItem('nomUsuari') || 'anonim'
    fetch('./api/guardar_puntuacio.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nom: nom, total: total, correctes: correctes })
    })
      .then(r => r.json().catch(() => { throw new Error('Resposta no JSON') }))
      .then(json => {
        if (json && json.ok) {
          if (json.saved_to_db) {
            console.log('Puntuació guardada a la base de dades.')
            // mostrar pequeña nota en UI
            const note = document.createElement('div')
            note.className = 'small-muted'
            note.style.marginTop = '10px'
            note.textContent = 'Puntuació guardada a la base de dades.'
            const finalDiv = document.querySelector('.final')
            if (finalDiv) finalDiv.appendChild(note)
          } else {
            console.warn('Puntuació no guardada a BD; s\'ha guardat a fitxer local.')
            const note = document.createElement('div')
            note.className = 'small-muted'
            note.style.marginTop = '10px'
            note.textContent = 'Puntuació guardada en fitxer local (fallada DB).'
            const finalDiv = document.querySelector('.final')
            if (finalDiv) finalDiv.appendChild(note)
          }
        } else {
          console.warn('Resposta inesperada de guardar_puntuacio.php', json)
        }
      })
      .catch(err => {
        console.error('Error guardant puntuacio:', err)
        const note = document.createElement('div')
        note.className = 'small-muted'
        note.style.marginTop = '10px'
        note.textContent = 'Error en guardar puntuació: ' + (err.message || err)
        const finalDiv = document.querySelector('.final')
        if (finalDiv) finalDiv.appendChild(note)
      })
      .finally(() => {
        // fetch leaderboard (top 10 by correct answers)
        fetch('./api/get_scores.php')
          .then(r => r.json())
          .then(data => {
            if (!data || !data.ok || !Array.isArray(data.scores)) return
            const scores = data.scores
            const div = document.createElement('div')
            div.className = 'leaderboard'
            let html = '<h3>Classificació (Top 10 - millor per usuari)</h3>'
            html += '<table border="1" cellpadding="6"><tr><th>Pos</th><th>Nom</th><th>Correctes</th></tr>'
            for (let i = 0; i < scores.length; i++) {
              const s = scores[i]
              html += `<tr><td>${i + 1}</td><td>${escapeHtml(s.nom)}</td><td>${s.correctes}</td></tr>`
            }
            html += '</table>'
            div.innerHTML = html
            const finalDiv = document.querySelector('.final')
            if (finalDiv) finalDiv.parentNode.insertBefore(div, finalDiv.nextSibling)
          }).catch(err => console.warn('No s\'ha pogut carregar la classificació:', err))
      })
  }

  // --- Inicialización y manejadores globales ---
  // Fallback: capturar submit del formulario desde el documento para evitar problemas de sincronización
  document.addEventListener('submit', function (e) {
    const form = e.target
    if (!form || form.id !== 'formUsuari') return
    e.preventDefault()
    // obtener nombre directamente del DOM (por si formUsuari no está en el ámbito esperado)
    const nomInput = document.getElementById('nomUsuari')
    const nom = nomInput ? nomInput.value.trim() : ''
    if (!nom) {
      // mostrar un mensaje de error si es necesario
      alert('Si us plau, introdueix el teu nom abans de començar.')
      return
    }
    try {
      localStorage.setItem('nomUsuari', nom)
    } catch (err) {
      console.warn('No s’ha pogut accedir a localStorage', err)
    }
    // mostrar salutacio si existen las funciones
    if (typeof mostrarSalutacio === 'function') mostrarSalutacio(nom)
    // feedback de carga
    if (tempsDiv) tempsDiv.textContent = 'Carregant preguntes...'
    // iniciar juego
    if (typeof iniciarJoc === 'function') {
      iniciarJoc()
    } else {
      console.error('iniciarJoc no està definida')
      if (tempsDiv) tempsDiv.textContent = 'Error: iniciarJoc no definida.'
    }
  })

  // Manejador directo para el botón (más robusto contra navegadores que hagan submit)
  const btnComencar = document.getElementById('btnComencar')
  if (btnComencar) {
    btnComencar.addEventListener('click', function (ev) {
      console.log('btnComencar clicked')
      const nomInput = document.getElementById('nomUsuari')
      const nom = nomInput ? nomInput.value.trim() : ''
      if (!nom) { alert('Si us plau, introdueix el teu nom abans de començar.'); return }
      // Si el formulario soporta requestSubmit, utilízalo para que el manejador central corra
      if (formUsuari && typeof formUsuari.requestSubmit === 'function') {
        console.log('Using form.requestSubmit() to trigger submit handler')
        formUsuari.requestSubmit()
        return
      }
      // Fallback: almacenar manualmente e iniciar
      try { localStorage.setItem('nomUsuari', nom) } catch (e) { console.warn('localStorage set failed', e) }
      if (typeof mostrarSalutacio === 'function') mostrarSalutacio(nom)
      if (tempsDiv) tempsDiv.textContent = 'Carregant preguntes...'
      if (typeof iniciarJoc === 'function') iniciarJoc()
    })
  }

  // Final: comprobar usuario una vez todo inicializado
  comprovarUsuari()
})

// Fisher-Yates shuffle (devuelve una nueva matriz)
function shuffleArray (arr) {
  const a = arr.slice()
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    const tmp = a[i]
    a[i] = a[j]
    a[j] = tmp
  }
  return a
}
