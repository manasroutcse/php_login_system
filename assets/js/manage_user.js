fetch('add_user.php', {
  method: 'POST',
  body: formData
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    showToast(data.message, 'success');
    setTimeout(() => location.reload(), 2000);
  } else {
    showToast(data.message, 'error');
  }
})
.catch(() => showToast('❌ Network error, please try again.', 'error'));
