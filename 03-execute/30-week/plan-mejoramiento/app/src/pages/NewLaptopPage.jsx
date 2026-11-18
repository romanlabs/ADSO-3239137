import { useNavigate } from 'react-router-dom';
import { createLaptop, listCategories } from '../api/client.js';
import ErrorMessage from '../components/ErrorMessage.jsx';
import LaptopForm from '../components/LaptopForm.jsx';
import useLoad from '../components/useLoad.js';

/** HU-03, CA-03.1: available units start equal to the total; the server computes them. */
export default function NewLaptopPage() {
  const navigate = useNavigate();
  const categories = useLoad(() => listCategories(), []);

  async function create(body) {
    const laptop = await createLaptop(body);
    navigate(`/laptops/${laptop.id}`, { replace: true });
  }

  return (
    <section>
      <h1>Nuevo portátil</h1>
      <ErrorMessage error={categories.error} />
      {categories.data && <LaptopForm categories={categories.data} submitLabel="Crear portátil" onSubmit={create} />}
    </section>
  );
}
