import { useEffect, useState } from 'react';
import { getStudent } from '../api/client.js';

// A loan carries only student_id (api-contract §3.6), so the list asks GET /students/{id} once per student and keeps
// the names for the rest of the session.
const cache = new Map();

export default function useStudentNames(ids) {
  const key = [...new Set(ids)].sort().join(',');
  const [names, setNames] = useState({});

  useEffect(() => {
    let cancelled = false;
    const unique = key ? key.split(',') : [];
    Promise.all(
      unique.map((id) => {
        if (!cache.has(id)) {
          cache.set(
            id,
            getStudent(id)
              .then((student) => student.full_name)
              .catch(() => {
                cache.delete(id);
                return 'Estudiante';
              }),
          );
        }
        return cache.get(id).then((name) => [id, name]);
      }),
    ).then((pairs) => !cancelled && setNames(Object.fromEntries(pairs)));
    return () => {
      cancelled = true;
    };
  }, [key]);

  return names;
}
