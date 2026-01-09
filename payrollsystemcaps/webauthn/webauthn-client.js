function base64ToArrayBuffer(b64u) {
  const base64 = b64u.replace(/-/g, '+').replace(/_/g, '/');
  const pad = base64.length % 4;
  const base64Pad = base64 + (pad ? '='.repeat(4 - pad) : '');
  const str = atob(base64Pad);
  const bytes = new Uint8Array(str.length);

  for (let i = 0; i < str.length; i++) bytes[i] = str.charCodeAt(i);
  return bytes.buffer;
}

function arrayBufferToB64u(buf) {
  const bytes = new Uint8Array(buf);
  let binary = '';
  for (let i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
  let b64 = btoa(binary);
  return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function prepareCreateOptions(server) {
  const options = server.publicKey || server;

  options.challenge = base64ToArrayBuffer(options.challenge);
  options.user.id = base64ToArrayBuffer(options.user.id);
  if (options.excludeCredentials) {
    options.excludeCredentials = options.excludeCredentials.map(c => ({
      id: base64ToArrayBuffer(c.id),
      type: c.type,
      transports: c.transports || undefined
    }));
  }
  return options;
}

function prepareGetOptions(server) {
  const options = server; 

  options.challenge = base64ToArrayBuffer(options.challenge);

  if (options.allowCredentials) {
    options.allowCredentials = options.allowCredentials.map(c => ({
      id: base64ToArrayBuffer(c.id),
      type: c.type,
      transports: c.transports || undefined
    }));
  }

  return options;
}


async function postJSON(url, data) {
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: 'include',
    body: JSON.stringify(data),
  });

  const text = await res.text();

  try {
    return JSON.parse(text);
  } catch (e) {
    console.error("Invalid JSON response:", text);
    throw e;
  }
}

document.getElementById('register').addEventListener('click', async () => {
  const emp = document.getElementById('employeeID').value;

  const createArgs = await (await fetch(`register_start.php?employee_id=${encodeURIComponent(emp)}`, { credentials: 'include' })).json();

  const publicKey = prepareCreateOptions(createArgs);

  const cred = await navigator.credentials.create({ publicKey });

  const response = {
    id: cred.id,
    rawId: arrayBufferToB64u(cred.rawId),
    type: cred.type,
    clientDataJSON: arrayBufferToB64u(cred.response.clientDataJSON),
    attestationObject: arrayBufferToB64u(cred.response.attestationObject)
  };

  const result = await postJSON('register_finish.php', response);
});

document.getElementById('time-in').addEventListener('click', async () => {
  doAuth('time_in');
});
document.getElementById('time-out').addEventListener('click', async () => {
  doAuth('time_out');
});

async function doAuth(action) {
  Swal.fire({
    title: "Authenticating...",
    text: "Please scan your fingerprint.",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    const emp = document.getElementById('employeeID').value;
    const getArgs = await (await fetch(`auth_start.php?employee_id=${encodeURIComponent(emp)}`)).json();

    const publicKey = prepareGetOptions(getArgs.publicKey);
    const cred = await navigator.credentials.get({ publicKey });

    const payload = {
      credential: {
        id: cred.id,
        rawId: arrayBufferToB64u(cred.rawId),
        type: cred.type,
        response: {
          clientDataJSON: arrayBufferToB64u(cred.response.clientDataJSON),
          authenticatorData: arrayBufferToB64u(cred.response.authenticatorData),
          signature: arrayBufferToB64u(cred.response.signature),
          userHandle: cred.response.userHandle ? arrayBufferToB64u(cred.response.userHandle) : null
        }
      },
      action
    };

    const result = await postJSON('auth_finish.php', payload);

    if (result.status) {
      Swal.fire({
        icon: "success",
        title: "Success!",
        text: `Your ${action.replace("_", " ")} was recorded successfully.`,
        showConfirmButton: false,
        timer: 2000
      }).then(() => {
        location.reload()
        return result;
      });
    } else {
      Swal.fire({
          icon: "error",
          title: "Authentication Failed",
          text: "Fingerprint not recognized. Please try again."
      });
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
        icon: "error",
        title: "Error",
        text: "Something went wrong during fingerprint verification."
    });
  }
}
