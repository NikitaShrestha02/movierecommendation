<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Movie Recommendation</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 16px;
        }

        .signup-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 580px;
            padding: 36px 32px;
            text-align: left;
        }

        .signup-brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .signup-brand img {
            height: 42px;
            width: auto;
            display: inline-block;
            margin-bottom: 8px;
        }

        .signup-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #60a5fa;
            background-color: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.25);
            padding: 3px 9px;
            border-radius: 4px;
        }

        .signup-title {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            margin: 12px 0 6px 0;
            text-align: center;
            letter-spacing: -0.01em;
        }

        .signup-subtitle {
            color: #94a3b8;
            font-size: 13.5px;
            text-align: center;
            margin: 0 0 24px 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 16px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control {
            width: 100%;
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .error-message {
            display: none;
            color: #f87171;
            font-size: 12px;
            margin-top: 5px;
            line-height: 1.4;
        }

        /* Genre Pill Selector */
        .genres-section {
            margin: 20px 0;
            padding: 16px;
            background-color: #131924;
            border: 1px solid #232d3f;
            border-radius: 6px;
        }

        .genres-title {
            color: #f1f5f9;
            font-size: 13.5px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .genres-desc {
            color: #94a3b8;
            font-size: 12px;
            margin: 0 0 12px 0;
        }

        .genre-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .genre-tag {
            display: inline-flex;
            align-items: center;
            background-color: #1a2230;
            border: 1px solid #2d384c;
            color: #cbd5e1;
            font-size: 13px;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            user-select: none;
        }

        .genre-tag input {
            margin-right: 6px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .genre-tag:hover {
            border-color: #3b82f6;
            color: #ffffff;
            background-color: #242f42;
        }

        .signup-btn {
            width: 100%;
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
            margin-top: 8px;
        }

        .signup-btn:hover {
            background-color: #1d4ed8;
        }

        .card-links {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #242e40;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .card-links a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .card-links a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .signup-card {
                padding: 24px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-card">
        <div class="signup-brand">
            <img src="LOGOO.png" alt="Logo">
            <div><span class="signup-badge">New Registration</span></div>
        </div>

        <h1 class="signup-title">Create Your Account</h1>
        <p class="signup-subtitle">Join us to receive personalized movie recommendations tailored to your taste</p>

        <form name="signupForm" method="post" action="signupdata.php" onsubmit="validateForm(event)">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                    <div id="nameError" class="error-message">Name can only contain alphabetic characters and spaces.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
                    <div id="emailError" class="error-message">Invalid email format.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" class="form-control" placeholder="10-digit number" required>
                    <div id="contactError" class="error-message">Contact must be 10 digits.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address / City</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Kathmandu, Nepal" required>
                    <div id="addressError" class="error-message">Address is required.</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="Password">Account Password</label>
                <input type="password" id="Password" name="Password" class="form-control" placeholder="8-15 characters with letter, number, and symbol" required>
                <div id="passwordError" class="error-message">Password must be 8-15 characters long, and include at least one letter, one number, and one special character.</div>
            </div>

            <!-- Genre Preferences -->
            <div class="genres-section">
                <h4 class="genres-title">Favorite Movie Genres</h4>
                <p class="genres-desc">Select genres you love to jumpstart your personalized KNN recommendations:</p>
                <div class="genre-tags">
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Action">Action</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Drama">Drama</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Comedy">Comedy</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Romance">Romance</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Sci-Fi">Sci-Fi</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Horror">Horror</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Fantasy">Fantasy</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Thriller">Thriller</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Nepali">Nepali</label>
                    <label class="genre-tag"><input type="checkbox" name="genres[]" value="Romance Comedy">Romance Comedy</label>
                </div>
            </div>

            <button type="submit" class="signup-btn">Create Account</button>
        </form>

        <div class="card-links">
            Already have an account? <a href="nlogin.php">Log In</a>
        </div>
    </div>

    <script>
        function validateForm(event) {
            let isValid = true;
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            const form = document.forms['signupForm'];
            const name = form['name'];
            const address = form['address'];
            const email = form['email'];
            const contact = form['contact'];
            const password = form['Password'];
            const nameRegex = /^[A-Za-z\s]+$/;

            if (name.value.trim() === '') {
                isValid = false;
                document.getElementById('nameError').innerText = 'Name is required.';
                document.getElementById('nameError').style.display = 'block';
            } else if (!nameRegex.test(name.value.trim())) {
                isValid = false;
                document.getElementById('nameError').innerText = 'Name can only contain alphabetic characters and spaces.';
                document.getElementById('nameError').style.display = 'block';
            }

            if (address.value.trim() === '') {
                isValid = false;
                document.getElementById('addressError').innerText = 'Address is required.';
                document.getElementById('addressError').style.display = 'block';
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value.trim() === '') {
                isValid = false;
                document.getElementById('emailError').innerText = 'Email is required.';
                document.getElementById('emailError').style.display = 'block';
            } else if (!emailRegex.test(email.value.trim())) {
                isValid = false;
                document.getElementById('emailError').innerText = 'Invalid email format.';
                document.getElementById('emailError').style.display = 'block';
            }

            const contactPattern = /^\d{10}$/;
            if (!contactPattern.test(contact.value.trim())) {
                isValid = false;
                document.getElementById('contactError').innerText = 'Contact must be exactly 10 digits.';
                document.getElementById('contactError').style.display = 'block';
            }

            const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,15}$/;
            if (password.value.trim() === '') {
                isValid = false;
                document.getElementById('passwordError').innerText = 'Password is required.';
                document.getElementById('passwordError').style.display = 'block';
            } else if (!passwordPattern.test(password.value.trim())) {
                isValid = false;
                document.getElementById('passwordError').innerText = 'Password must be 8-15 characters long, including at least one letter, one number, and one special character.';
                document.getElementById('passwordError').style.display = 'block';
            }

            if (!isValid) {
                event.preventDefault(); 
            }
        }
    </script>
</body>
</html>