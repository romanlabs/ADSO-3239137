import { useEffect, useState } from 'react';
import { fetchLaptopPhoto } from '../api/client.js';

/**
 * The photo is behind the token, so an <img src> cannot fetch it directly: it is downloaded as a blob and shown
 * through an object URL. `photoKey` is only used to know whether there is one and to refetch when it changes.
 */
export default function LaptopPhoto({ laptopId, photoKey, alt, className = 'photo' }) {
  const [url, setUrl] = useState(null);

  useEffect(() => {
    if (!photoKey) {
      setUrl(null);
      return undefined;
    }
    let objectUrl = null;
    let cancelled = false;
    fetchLaptopPhoto(laptopId)
      .then((blob) => {
        if (cancelled) return;
        objectUrl = URL.createObjectURL(blob);
        setUrl(objectUrl);
      })
      .catch(() => !cancelled && setUrl(null));
    return () => {
      cancelled = true;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [laptopId, photoKey]);

  if (!url) return <div className={`${className} photo-empty`} aria-label="Sin foto">Sin foto</div>;
  return <img className={className} src={url} alt={alt} />;
}
