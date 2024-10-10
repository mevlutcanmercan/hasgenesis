document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const birthdayInput = form.querySelector("input[name='birthday_users']");
    const phoneInput = document.querySelector("#telefon"); // ID ile seçtik

    // JS kısmında tam telefon numarasını alalım
const iti = window.intlTelInput(phoneInput, {
    separateDialCode: true,
    initialCountry: "tr",
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
});

// Tam telefon numarası almak için:
let fullPhoneNumber = iti.getNumber();
console.log(fullPhoneNumber); // Tam numara (örneğin: +905xxxxxxxxx)

    // Doğum tarihi kontrolü
    birthdayInput.addEventListener("change", function () {
        const today = new Date();
        const selectedDate = new Date(birthdayInput.value);

        if (selectedDate > today) {
            Swal.fire({
                icon: "error",
                title: "Geçersiz Doğum Tarihi",
                text: "Doğum tarihi bugünden ileri olamaz!",
                confirmButtonText: "Tamam"
            });
            birthdayInput.value = "";
        }
    });

    // Form gönderimi sırasında doğrulama
    form.addEventListener("submit", function (e) {
        let valid = true;
        const today = new Date();
        const selectedDate = new Date(birthdayInput.value);

        // Doğum tarihi kontrolü
        if (selectedDate > today) {
            valid = false;
            Swal.fire({
                icon: "error",
                title: "Geçersiz Doğum Tarihi",
                text: "Doğum tarihi bugünden ileri olamaz!",
                confirmButtonText: "Tamam"
            });
            birthdayInput.value = "";
        }


    });
});

// İlk harfleri büyük yapan fonksiyon (Türkçe karakter desteği ile)
function capitalizeFirstLetters(input) {
    return input.replace(/(^|\s)([a-zâêîôûğşçö]*)/gi, function (match) {
        return match.charAt(0).toUpperCase() + match.slice(1);
    });
}

// Ad ve soyad alanları için 'input' eventine listener ekle
document.getElementById('name_users').addEventListener('input', function () {
    this.value = capitalizeFirstLetters(this.value);
});

document.getElementById('surname_users').addEventListener('input', function () {
    this.value = capitalizeFirstLetters(this.value);
});


function validatePassword() {
    const password = document.getElementById('password').value;
    const passwordError = document.getElementById('password-error');
    const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{7,}$/;

    if (!passwordPattern.test(password)) {
        passwordError.style.display = 'block'; // Hata mesajını göster
        return false; // Formun gönderilmesini engeller
    } else {
        passwordError.style.display = 'none'; // Hata mesajını gizler
        alert('Şifre uygun, form gönderiliyor!'); // Bu kısmı test için ekleyebilirsin
        return true;
    }
}
