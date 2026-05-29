class RequestManager {
  async postJSON(url, data){
    const res = await fetch(url, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(data)
    });
    if(!res.ok) throw new Error('Network response not ok');
    return res.json();
  }
}

const rm = new RequestManager();

document.addEventListener('DOMContentLoaded', ()=>{
  const startBtn = document.getElementById('startBtn');
  const submitBtn = document.getElementById('submitBtn');
  const guessInput = document.getElementById('guessInput');
  const timerBar = document.getElementById('timerBar');
  const resultEl = document.getElementById('result');
  const realNumberEl = document.getElementById('realNumber');
  const outcomeEl = document.getElementById('outcome');
  const correctCountEl = document.getElementById('correctCount');
  const missCountEl = document.getElementById('missCount');

  let countdownTimer = null;
  let roundActive = false;
  let correct = 0;
  let misses = 0;

  function resetUI(){
    timerBar.style.width = '0%';
    resultEl.classList.add('hidden');
    realNumberEl.textContent = '-';
    outcomeEl.textContent = '';
  }

  function enablePlay(enable){
    guessInput.disabled = !enable;
    submitBtn.disabled = !enable;
    if(enable) guessInput.focus();
  }

  async function endRound(guessValue){
    roundActive = false;
    enablePlay(false);
    clearInterval(countdownTimer);
    try{
      const payload = { guess: guessValue === undefined ? null : Number(guessValue) };
      const res = await rm.postJSON('guess.php', payload);
      realNumberEl.textContent = res.real;
      if(res.correct){
        outcomeEl.textContent = '🎉 Correct!';
        correct++;
        correctCountEl.textContent = String(correct);
      } else {
        outcomeEl.textContent = '❌ Miss — your guess: ' + (res.guess===null? '—':res.guess);
        misses++;
        missCountEl.textContent = String(misses);
      }
      resultEl.classList.remove('hidden');
    }catch(err){
      outcomeEl.textContent = 'Error: '+err.message;
      resultEl.classList.remove('hidden');
    }
  }

  startBtn.addEventListener('click', ()=>{
    resetUI();
    roundActive = true;
    enablePlay(true);
    guessInput.value = '';
    resultEl.classList.add('hidden');
    // 10s countdown
    const start = Date.now();
    const duration = 10000;
    timerBar.style.width = '100%';
    const tick = ()=>{
      const elapsed = Date.now() - start;
      const pct = Math.max(0, 100 - (elapsed/duration)*100);
      timerBar.style.width = pct + '%';
      if(elapsed >= duration){
        // time's up
        endRound(guessInput.value ? Number(guessInput.value) : null);
      }
    };
    clearInterval(countdownTimer);
    countdownTimer = setInterval(()=>{
      if(!roundActive){ clearInterval(countdownTimer); return; }
      tick();
    }, 100);
  });

  submitBtn.addEventListener('click', ()=>{
    if(!roundActive) return;
    const val = guessInput.value ? Number(guessInput.value) : null;
    endRound(val);
  });

});
