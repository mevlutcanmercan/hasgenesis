// SweetAlert ile hata mesajı gösterimi
function showErrorAlert(message) {
    Swal.fire({
        icon: 'error',
        title: 'Hata!',
        text: message,
        confirmButtonText: 'Tamam'
    });
}

// SweetAlert ile başarı mesajı gösterimi
function showSuccessAlert(message, redirectUrl) {
    Swal.fire({
        icon: 'success',
        title: 'Başarılı!',
        text: message,
        confirmButtonText: 'Tamam'
    }).then((result) => {
        if (result.isConfirmed && redirectUrl) {
            window.location.href = redirectUrl;
        }
    });
}

// SweetAlert ile uyarı mesajı gösterimi
function showWarningAlert(message) {
    Swal.fire({
        icon: 'warning',
        title: 'Uyarı!',
        text: message,
        confirmButtonText: 'Tamam'
    });
}