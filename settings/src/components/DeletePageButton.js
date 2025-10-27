import { useState } from "@wordpress/element";
import { store as noticesStore } from "@wordpress/notices";
import { store as coreDataStore } from "@wordpress/core-data";
import { useSelect, useDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import {
  Button,
  Spinner,
  __experimentalConfirmDialog as ConfirmDialog
} from "@wordpress/components";

const DeletePageButton = ({ pageId }) => {
  const [isOpen, setIsOpen] = useState(false);
  const { createSuccessNotice, createErrorNotice } = useDispatch(noticesStore);
  // useSelect returns a list of selectors if you pass the store handle
  // instead of a callback:
  const { getLastEntityDeleteError } = useSelect(coreDataStore);
  const handleConfirm = () => {
    console.log("Confirmed!");
    setIsOpen(false);
    handleDelete();
  };

  const handleCancel = () => {
    console.log("Cancelled!");
    setIsOpen(false);
  };
  const handleDelete = async () => {
    const success = await deleteEntityRecord("postType", "page", pageId);
    if (success) {
      // Tell the user the operation succeeded:
      createSuccessNotice("The page was deleted!", {
        type: "snackbar"
      });
    } else {
      // We use the selector directly to get the fresh error *after* the deleteEntityRecord
      // have failed.
      const lastError = getLastEntityDeleteError("postType", "page", pageId);
      const message =
        (lastError?.message || "There was an error.") +
        " Please refresh the page and try again.";
      // Tell the user how exactly the operation has failed:
      createErrorNotice(message, {
        type: "snackbar"
      });
    }
  };
  const { deleteEntityRecord } = useDispatch(coreDataStore);
  const { isDeleting, error } = useSelect(
    (select) => ({
      isDeleting: select(coreDataStore).isDeletingEntityRecord(
        "postType",
        "page",
        pageId
      ),
      error: select(coreDataStore).getLastEntityDeleteError(
        "postType",
        "page",
        pageId
      )
    }),
    [pageId]
  );
  useEffect(() => {
    if (error) {
      // Display the error
    }
  }, [error]);
  return (
    <>
      <ConfirmDialog
        isOpen={isOpen}
        onConfirm={handleConfirm}
        onCancel={handleCancel}
      >
        Are you sure? <strong>This action cannot be undone!</strong>
      </ConfirmDialog>
      <Button
        variant="primary"
        onClick={() => setIsOpen(true)}
        disabled={isDeleting}
      >
        {isDeleting ? (
          <>
            <Spinner />
            Deleting...
          </>
        ) : (
          "Delete"
        )}
      </Button>
    </>
  );
};

export default DeletePageButton;
