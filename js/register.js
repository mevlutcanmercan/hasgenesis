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
